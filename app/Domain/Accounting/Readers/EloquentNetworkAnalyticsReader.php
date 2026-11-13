<?php

namespace App\Domain\Accounting\Readers;

use App\Domain\Accounting\Models\CustomerUsageDaily;
use App\Domain\Accounting\Models\PartnerUsageDaily;
use App\Domain\Accounting\Models\RadiusAccountingSample;
use App\Domain\Accounting\Models\RadiusSession;
use App\Domain\Accounting\Models\RouterQueueSample;
use App\Domain\Network\Contracts\NetworkAnalyticsReader;
use App\Domain\Network\Dto\CustomerUsageQuery;
use App\Domain\Network\Dto\PaginatedSessions;
use App\Domain\Network\Dto\PartnerUsageQuery;
use App\Domain\Network\Dto\SessionQuery;
use App\Domain\Network\Dto\ThroughputQuery;
use App\Domain\Network\Dto\ThroughputSeries;
use App\Domain\Network\Dto\UsageSummary;
use App\Models\Pelanggan;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Read model over deduplicated accounting aggregates. Partner scope is applied
 * in every query; a partner can never read another partner's traffic.
 */
final class EloquentNetworkAnalyticsReader implements NetworkAnalyticsReader
{
    public function customerUsage(CustomerUsageQuery $query): UsageSummary
    {
        $customer = Pelanggan::query()->find($query->customerId);

        if ($customer === null) {
            throw (new ModelNotFoundException())->setModel(Pelanggan::class, [$query->customerId]);
        }

        if (! $query->scope->allows($customer->mitra_id !== null ? (int) $customer->mitra_id : null)) {
            throw (new ModelNotFoundException())->setModel(Pelanggan::class, [$query->customerId]);
        }

        $rows = CustomerUsageDaily::query()
            ->where('customer_id', $query->customerId)
            ->whereBetween('usage_date', [$query->from->toDateString(), $query->to->toDateString()])
            ->orderBy('usage_date')
            ->get();

        $hasSessions = RadiusSession::query()
            ->where('customer_id', $query->customerId)
            ->where('start_at', '<', $query->to->endOfDay())
            ->where(function ($builder) use ($query) {
                $builder->whereNull('stop_at')->orWhere('stop_at', '>=', $query->from->startOfDay());
            })
            ->exists();

        $upload = (int) $rows->sum('upload_bytes');
        $download = (int) $rows->sum('download_bytes');

        [$peakDown, $peakUp] = $this->peaksForCustomer($query);

        return new UsageSummary(
            scopeType: 'customer',
            scopeId: $query->customerId,
            from: $query->from,
            to: $query->to,
            uploadBytes: $upload,
            downloadBytes: $download,
            totalBytes: $upload + $download,
            peakDownloadMbps: $peakDown,
            peakUploadMbps: $peakUp,
            completeness: $this->aggregateCompleteness($rows, $hasSessions),
            daily: $rows->map(fn (CustomerUsageDaily $row) => [
                'usage_date' => $row->usage_date->toDateString(),
                'upload_bytes' => (int) $row->upload_bytes,
                'download_bytes' => (int) $row->download_bytes,
                'total_bytes' => (int) $row->total_bytes,
                'completeness' => $row->completeness,
            ])->all(),
        );
    }

    public function partnerUsage(PartnerUsageQuery $query): UsageSummary
    {
        if (! $query->scope->allows($query->partnerId)) {
            // Non-disclosing: another tenant's partner looks absent.
            throw (new ModelNotFoundException())->setModel(PartnerUsageDaily::class, [$query->partnerId]);
        }

        $rows = PartnerUsageDaily::query()
            ->where('partner_id', $query->partnerId)
            ->whereBetween('usage_date', [$query->from->toDateString(), $query->to->toDateString()])
            ->orderBy('usage_date')
            ->get();

        if ($rows->isEmpty()) {
            $rows = $this->derivePartnerRows($query);
        }

        $upload = (int) $rows->sum('upload_bytes');
        $download = (int) $rows->sum('download_bytes');

        $peakDown = $rows->max('peak_download_mbps');
        $peakUp = $rows->max('peak_upload_mbps');

        return new UsageSummary(
            scopeType: 'partner',
            scopeId: $query->partnerId,
            from: $query->from,
            to: $query->to,
            uploadBytes: $upload,
            downloadBytes: $download,
            totalBytes: $upload + $download,
            peakDownloadMbps: $peakDown !== null ? (float) $peakDown : null,
            peakUploadMbps: $peakUp !== null ? (float) $peakUp : null,
            completeness: $this->aggregateCompleteness($rows, $rows->isNotEmpty()),
            daily: $rows->map(fn ($row) => [
                'usage_date' => $row->usage_date->toDateString(),
                'upload_bytes' => (int) $row->upload_bytes,
                'download_bytes' => (int) $row->download_bytes,
                'total_bytes' => (int) $row->total_bytes,
                'completeness' => $row->completeness,
            ])->values()->all(),
        );
    }

    public function activeSessions(SessionQuery $query): PaginatedSessions
    {
        $builder = RadiusSession::query();

        if (! $query->scope->isCentral()) {
            $builder->where('partner_id', $query->scope->partnerId);
        }

        if ($query->scope->isCentral() && $query->partnerId !== null) {
            $builder->where('partner_id', $query->partnerId);
        }

        if ($query->customerId !== null) {
            $builder->where('customer_id', $query->customerId);
        }

        if ($query->activeOnly) {
            $builder->whereIn('status', [RadiusSession::ACTIVE, RadiusSession::STALE]);
        }

        $total = (clone $builder)->count();
        $perPage = max(1, min(100, $query->perPage));

        $sessions = $builder
            ->orderByDesc('last_sample_at')
            ->orderByDesc('id')
            ->forPage($query->page, $perPage)
            ->get();

        return new PaginatedSessions(
            items: $sessions->map(fn (RadiusSession $session) => [
                'id' => (string) $session->id,
                'username' => $session->username,
                'customer_id' => $session->customer_id !== null ? (string) $session->customer_id : null,
                'partner_id' => $session->partner_id !== null ? (string) $session->partner_id : null,
                'nas_device_id' => (string) $session->nas_device_id,
                'framed_ip_address' => $session->framed_ip_address,
                'start_at' => $session->start_at?->toIso8601ZuluString(),
                'last_sample_at' => $session->last_sample_at?->toIso8601ZuluString(),
                'stop_at' => $session->stop_at?->toIso8601ZuluString(),
                'status' => $session->status,
                'input_bytes' => (int) $session->input_bytes_64,
                'output_bytes' => (int) $session->output_bytes_64,
                'estimated_close' => (bool) $session->estimated_close,
            ])->all(),
            total: $total,
            page: $query->page,
            perPage: $perPage,
        );
    }

    public function throughputSeries(ThroughputQuery $query): ThroughputSeries
    {
        if (! $query->scope->allows($query->partnerId)) {
            throw (new ModelNotFoundException())->setModel(RouterQueueSample::class, [$query->partnerId]);
        }

        $samples = RouterQueueSample::query()
            ->where('partner_id', $query->partnerId)
            ->where('sampled_at', '>=', $query->from->startOfDay())
            ->where('sampled_at', '<', $query->to->endOfDay())
            ->orderBy('sampled_at')
            ->limit(5000)
            ->get();

        return new ThroughputSeries(
            partnerId: $query->partnerId,
            from: $query->from,
            to: $query->to,
            resolutionSeconds: $query->resolutionSeconds,
            samples: $samples->map(fn (RouterQueueSample $sample) => [
                'sampled_at' => $sample->sampled_at->toIso8601ZuluString(),
                'download_mbps' => round((int) $sample->current_rate_down_bps / 1_000_000, 3),
                'upload_mbps' => round((int) $sample->current_rate_up_bps / 1_000_000, 3),
                'sample_interval_seconds' => (int) $sample->sample_interval_seconds,
            ])->all(),
            completeness: $samples->isEmpty() ? CustomerUsageDaily::GAP : CustomerUsageDaily::COMPLETE,
        );
    }

    /**
     * @return array{0: ?float, 1: ?float}
     */
    private function peaksForCustomer(CustomerUsageQuery $query): array
    {
        $samples = RadiusAccountingSample::query()
            ->select(['radius_accounting_samples.*'])
            ->join('radius_sessions', 'radius_sessions.id', '=', 'radius_accounting_samples.radius_session_id')
            ->where('radius_sessions.customer_id', $query->customerId)
            ->where('radius_accounting_samples.sampled_at', '>=', $query->from->startOfDay())
            ->where('radius_accounting_samples.sampled_at', '<', $query->to->endOfDay())
            ->where('radius_accounting_samples.delta_valid', true)
            ->orderBy('radius_accounting_samples.sampled_at')
            ->limit(20000)
            ->get();

        $peakDown = null;
        $peakUp = null;
        $previousBySession = [];

        foreach ($samples as $sample) {
            $sessionId = (int) $sample->radius_session_id;
            $previous = $previousBySession[$sessionId] ?? null;
            $previousBySession[$sessionId] = $sample->sampled_at;

            if ($previous === null) {
                continue;
            }

            $seconds = (int) CarbonImmutable::instance($previous)->diffInSeconds($sample->sampled_at);

            if ($seconds < 1) {
                continue;
            }

            $downMbps = ((int) $sample->output_delta_bytes * 8) / $seconds / 1_000_000;
            $upMbps = ((int) $sample->input_delta_bytes * 8) / $seconds / 1_000_000;

            $peakDown = $peakDown === null ? $downMbps : max($peakDown, $downMbps);
            $peakUp = $peakUp === null ? $upMbps : max($peakUp, $upMbps);
        }

        return [
            $peakDown !== null ? round($peakDown, 3) : null,
            $peakUp !== null ? round($peakUp, 3) : null,
        ];
    }

    private function aggregateCompleteness(iterable $rows, bool $hasEvidence): string
    {
        $rows = collect($rows);

        if ($rows->isEmpty()) {
            return $hasEvidence ? CustomerUsageDaily::GAP : CustomerUsageDaily::COMPLETE;
        }

        if ($rows->contains(fn ($row) => $row->completeness === CustomerUsageDaily::GAP)) {
            return CustomerUsageDaily::GAP;
        }

        if ($rows->contains(fn ($row) => $row->completeness === CustomerUsageDaily::ESTIMATED)) {
            return CustomerUsageDaily::ESTIMATED;
        }

        return CustomerUsageDaily::COMPLETE;
    }

    /**
     * Fallback when partner rollups have not run yet: aggregate the customer
     * rows directly (same source of truth, never a second measurement).
     */
    private function derivePartnerRows(PartnerUsageQuery $query)
    {
        $rows = CustomerUsageDaily::query()
            ->where('partner_id', $query->partnerId)
            ->whereBetween('usage_date', [$query->from->toDateString(), $query->to->toDateString()])
            ->get()
            ->groupBy(fn (CustomerUsageDaily $row) => $row->usage_date->toDateString())
            ->map(function ($group, $date) {
                $upload = (int) $group->sum('upload_bytes');
                $download = (int) $group->sum('download_bytes');

                return (object) [
                    'usage_date' => CarbonImmutable::parse($date),
                    'upload_bytes' => $upload,
                    'download_bytes' => $download,
                    'total_bytes' => $upload + $download,
                    'peak_download_mbps' => null,
                    'peak_upload_mbps' => null,
                    'completeness' => $group->contains(fn ($row) => $row->completeness === CustomerUsageDaily::ESTIMATED)
                        ? CustomerUsageDaily::ESTIMATED
                        : CustomerUsageDaily::COMPLETE,
                ];
            })
            ->values();

        return $rows;
    }
}
