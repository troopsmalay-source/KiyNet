<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Models\CustomerUsageDaily;
use App\Domain\Accounting\Models\PartnerUsageDaily;
use App\Domain\Accounting\Models\RadiusAccountingSample;
use App\Domain\Accounting\Models\RadiusSession;
use App\Domain\Accounting\Models\RouterQueueSample;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Builds customer/partner daily aggregates from deduplicated samples.
 *
 * RADIUS customer deltas and RouterOS queue rates are separate measurements:
 * bytes come from accounting deltas, peak throughput from queue samples.
 * They are never summed together.
 */
final class UsageRollupService
{
    public function __construct(
        private readonly ?int $calculationVersion = null,
    ) {
    }

    private function version(): int
    {
        return $this->calculationVersion ?? (int) config('network.accounting.calculation_version', 1);
    }

    public function rollupCustomerDay(int $customerId, CarbonInterface $date): ?CustomerUsageDaily
    {
        $dayStart = CarbonImmutable::parse($date)->utc()->startOfDay();
        $dayEnd = $dayStart->addDay();

        $sessions = RadiusSession::query()
            ->where('customer_id', $customerId)
            ->where('start_at', '<', $dayEnd)
            ->where(function ($query) use ($dayStart) {
                $query->whereNull('stop_at')->orWhere('stop_at', '>=', $dayStart);
            })
            ->get();

        $upload = 0;
        $download = 0;
        $coveredSeconds = 0;
        $sampleCount = 0;
        $estimated = false;
        $partnerId = null;

        foreach ($sessions as $session) {
            $partnerId ??= $session->partner_id;

            $before = RadiusAccountingSample::query()
                ->where('radius_session_id', $session->id)
                ->where('sampled_at', '<', $dayStart)
                ->orderByDesc('sampled_at')
                ->first();

            $inDay = RadiusAccountingSample::query()
                ->where('radius_session_id', $session->id)
                ->where('sampled_at', '>=', $dayStart)
                ->where('sampled_at', '<', $dayEnd)
                ->orderBy('sampled_at')
                ->get();

            // The first sample after this day closes an interval that may have
            // started before midnight; its day share belongs to this rollup.
            $nextAfterDay = RadiusAccountingSample::query()
                ->where('radius_session_id', $session->id)
                ->where('sampled_at', '>=', $dayEnd)
                ->orderBy('sampled_at')
                ->first();

            if ($nextAfterDay !== null) {
                $inDay->push($nextAfterDay);
            }

            $previousAt = $before?->sampled_at ?? $session->start_at;

            foreach ($inDay as $sample) {
                $sampleInDay = $sample->sampled_at->lessThan($dayEnd);

                if ($sampleInDay) {
                    $sampleCount++;
                }

                if (! $sample->delta_valid) {
                    // Out-of-order or counter reset: excluded from finalized totals.
                    if ($sampleInDay) {
                        $estimated = true;
                    }
                } else {
                    $sampleAt = CarbonImmutable::instance($sample->sampled_at);
                    $intervalStart = $previousAt !== null && $previousAt->lessThan($sample->sampled_at)
                        ? CarbonImmutable::instance($previousAt)
                        : $sampleAt;
                    $deltaTotal = (int) $sample->input_delta_bytes + (int) $sample->output_delta_bytes;
                    $dayKey = $dayStart->toDateString();

                    if ($intervalStart->lessThan($sampleAt)) {
                        $byDate = IntervalAllocator::split($intervalStart, $sampleAt, $deltaTotal);

                        $coveredSeconds += $byDate[$dayKey]['seconds'] ?? 0;

                        // A midnight-straddling interval is a proportional
                        // assumption for every day it touches.
                        if ($byDate[$dayKey]['estimated'] ?? false) {
                            $estimated = true;
                        }

                        // Upload/download split keeps the interval proportions.
                        $intervalSeconds = max(1, (int) $intervalStart->diffInSeconds($sampleAt));
                        $ratio = ($byDate[$dayKey]['seconds'] ?? 0) / $intervalSeconds;
                    } else {
                        // First sample of a session: no measurable interval. Bytes
                        // with no evidence of when they moved stay estimated.
                        $ratio = $sampleInDay ? 1.0 : 0.0;
                        if ($sampleInDay && $deltaTotal > 0) {
                            $estimated = true;
                        }
                    }

                    $upload += (int) round((int) $sample->input_delta_bytes * $ratio);
                    $download += (int) round((int) $sample->output_delta_bytes * $ratio);
                }

                $previousAt = $sample->sampled_at;
            }

            // Session still open past the day: final numbers may still grow.
            if ($session->stop_at === null && $session->last_sample_at !== null && $session->last_sample_at->lessThan($dayEnd)) {
                $estimated = true;
            }

            if ($session->status === RadiusSession::STALE) {
                $estimated = true;
            }
        }

        if ($sampleCount === 0) {
            CustomerUsageDaily::query()
                ->where('customer_id', $customerId)
                ->whereDate('usage_date', $dayStart->toDateString())
                ->delete();

            return null;
        }

        return CustomerUsageDaily::query()->updateOrCreate(
            ['customer_id' => $customerId, 'usage_date' => $dayStart->toDateString()],
            [
                'partner_id' => $partnerId,
                'upload_bytes' => $upload,
                'download_bytes' => $download,
                'total_bytes' => $upload + $download,
                'covered_seconds' => $coveredSeconds,
                'sample_count' => $sampleCount,
                'completeness' => $estimated ? CustomerUsageDaily::ESTIMATED : CustomerUsageDaily::COMPLETE,
                'calculation_version' => $this->version(),
                'calculated_at' => CarbonImmutable::now('UTC'),
            ],
        );
    }

    public function rollupPartnerDay(int $partnerId, CarbonInterface $date): ?PartnerUsageDaily
    {
        $day = CarbonImmutable::parse($date)->utc()->startOfDay();
        $dayEnd = $day->addDay();

        $customers = CustomerUsageDaily::query()
            ->where('partner_id', $partnerId)
            ->whereDate('usage_date', $day->toDateString())
            ->get();

        $queueSamples = RouterQueueSample::query()
            ->where('partner_id', $partnerId)
            ->where('sampled_at', '>=', $day)
            ->where('sampled_at', '<', $dayEnd)
            ->get();

        if ($customers->isEmpty() && $queueSamples->isEmpty()) {
            PartnerUsageDaily::query()
                ->where('partner_id', $partnerId)
                ->whereDate('usage_date', $day->toDateString())
                ->delete();

            return null;
        }

        $upload = (int) $customers->sum('upload_bytes');
        $download = (int) $customers->sum('download_bytes');

        $completeness = CustomerUsageDaily::COMPLETE;

        if ($customers->isEmpty()) {
            $completeness = CustomerUsageDaily::GAP;
        } elseif ($customers->contains(fn ($row) => $row->completeness === CustomerUsageDaily::GAP)) {
            $completeness = CustomerUsageDaily::GAP;
        } elseif ($customers->contains(fn ($row) => $row->completeness === CustomerUsageDaily::ESTIMATED)) {
            $completeness = CustomerUsageDaily::ESTIMATED;
        }

        // Without independent queue samples the aggregate cap cannot be proven.
        if ($queueSamples->isEmpty()) {
            $completeness = $completeness === CustomerUsageDaily::GAP
                ? CustomerUsageDaily::GAP
                : CustomerUsageDaily::ESTIMATED;
        }

        $peakDown = $queueSamples->isEmpty() ? null : round($queueSamples->max('current_rate_down_bps') / 1_000_000, 3);
        $peakUp = $queueSamples->isEmpty() ? null : round($queueSamples->max('current_rate_up_bps') / 1_000_000, 3);

        return PartnerUsageDaily::query()->updateOrCreate(
            ['partner_id' => $partnerId, 'usage_date' => $day->toDateString()],
            [
                'upload_bytes' => $upload,
                'download_bytes' => $download,
                'total_bytes' => $upload + $download,
                'peak_download_mbps' => $peakDown,
                'peak_upload_mbps' => $peakUp,
                'completeness' => $completeness,
                'calculation_version' => $this->version(),
                'calculated_at' => CarbonImmutable::now('UTC'),
            ],
        );
    }
}
