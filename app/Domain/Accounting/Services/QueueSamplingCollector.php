<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Models\RouterQueueSample;
use App\Domain\Network\Models\NasDevice;
use App\Domain\Network\RouterOs\RouterOsQueueReader;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Samples per-partner aggregate queue counters from a RouterOS NAS and stores
 * deduplicated rows in router_queue_samples.
 *
 * Design contract (see infra/mikrotik/partner-cap-queue.rsc.template):
 *  - queue_key = "<partner_id>:<queue name>" (or "default" per queue);
 *  - current_rate_*_bps is derived from the byte-delta of consecutive samples,
    never from instantaneous router counters, so a missing sample never
    fabricates throughput;
 *  - dedupe_key is stable per (nas, queue_key, sampled_at) so re-runs and
    duplicate scheduler fire are idempotent;
 *  - partner_id is resolved server-side from the queue key, never client input.
 */
final class QueueSamplingCollector
{
    public function __construct(
        private readonly RouterOsQueueReader $queueReader,
        private readonly ?int $sampleIntervalSeconds = null,
    ) {
    }

    private function interval(): int
    {
        return $this->sampleIntervalSeconds
            ?? (int) config('network.accounting.queue_sample_interval_seconds', 60);
    }

    /**
     * @param  array<int, array{queue_key: string, bytes_down_total: int, bytes_up_total: int}>  $counters
     * @return int number of samples stored (duplicates not counted)
     */
    public function collect(int $nasDeviceId, array $counters, ?CarbonImmutable $sampledAt = null): int
    {
        $nas = NasDevice::query()->findOrFail($nasDeviceId);
        $sampledAt ??= CarbonImmutable::now('UTC');
        $interval = $this->interval();

        $stored = 0;

        foreach ($counters as $counter) {
            $queueKey = (string) $counter['queue_key'];
            $partnerId = $this->partnerIdFromQueueKey($queueKey);
            $dedupeKey = hash('sha256', $nas->id.'|'.$queueKey.'|'.$sampledAt->toIso8601ZuluString());

            $previous = RouterQueueSample::query()
                ->where('nas_device_id', $nas->id)
                ->where('queue_key', $queueKey)
                ->orderByDesc('sampled_at')
                ->first();

            // Never fabricate a rate on the first sample.
            $downTotal = (int) $counter['bytes_down_total'];
            $upTotal = (int) $counter['bytes_up_total'];
            $rateDown = 0;
            $rateUp = 0;

            if ($previous !== null && $previous->sampled_at->lessThan($sampledAt)) {
                $seconds = $previous->sampled_at->diffInSeconds($sampledAt);

                if ($seconds >= 1) {
                    $rateDown = max(0, $downTotal - (int) $previous->bytes_down_total) * 8 / $seconds;
                    $rateUp = max(0, $upTotal - (int) $previous->bytes_up_total) * 8 / $seconds;
                }
            }

            try {
                RouterQueueSample::query()->create([
                    'nas_device_id' => $nas->id,
                    'partner_id' => $partnerId,
                    'queue_key' => $queueKey,
                    'sampled_at' => $sampledAt,
                    'current_rate_down_bps' => (int) round($rateDown),
                    'current_rate_up_bps' => (int) round($rateUp),
                    'bytes_down_total' => $downTotal,
                    'bytes_up_total' => $upTotal,
                    'sample_interval_seconds' => $interval,
                    'dedupe_key' => $dedupeKey,
                ]);

                $stored++;
            } catch (Throwable $exception) {
                if (! $this->isUniqueViolation($exception)) {
                    throw $exception;
                }
            }
        }

        return $stored;
    }

    public function collectFromRouter(int $nasDeviceId, ?string $queuePrefix = null): int
    {
        if (! $this->queueReader->isEnabled()) {
            return 0;
        }

        $prefix = $queuePrefix ?? 'partner-';

        return $this->collect($nasDeviceId, $this->queueReader->queueCounters($prefix));
    }

    /**
     * Queue names follow the cap design: "partner-<id>-down" / "partner-<id>-up"
     * (see infra/mikrotik/partner-cap-queue.rsc.template).
     */
    private function partnerIdFromQueueKey(string $queueKey): ?int
    {
        if (preg_match('/^partner-(\d+)-/', $queueKey, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    private function isUniqueViolation(Throwable $exception): bool
    {
        return $exception->getCode() === '23505'
            || (method_exists($exception, 'errorInfo') && (($exception->errorInfo[1] ?? null) === '23505'))
            || str_contains($exception->getMessage(), 'UNIQUE constraint failed');
    }
}
