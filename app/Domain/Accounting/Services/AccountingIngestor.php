<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Dto\AccountingEvent;
use App\Domain\Accounting\Dto\IngestResult;
use App\Domain\Accounting\Models\RadiusAccountingSample;
use App\Domain\Accounting\Models\RadiusSession;
use App\Domain\Network\Models\NasDevice;
use App\Domain\Network\Models\NetworkAccessAssignment;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Turns raw RADIUS accounting packets into deduplicated session samples.
 *
 * Rules:
 *  - interim values are cumulative snapshots, never summed;
 *  - 64-bit totals derived with Gigawords;
 *  - duplicate events are idempotent (stable dedupe key);
 *  - out-of-order samples are stored but excluded from rollups;
 *  - negative deltas are counter resets and never counted as traffic;
 *  - only metadata is persisted (no RADIUS secret/password material).
 */
final class AccountingIngestor
{
    public function ingest(AccountingEvent $event): IngestResult
    {
        $inputTotal = CounterNormalizer::total($event->inputOctets, $event->inputGigawords);
        $outputTotal = CounterNormalizer::total($event->outputOctets, $event->outputGigawords);

        $dedupeKey = $this->dedupeKey($event, $inputTotal, $outputTotal);
        $existing = RadiusAccountingSample::query()->where('dedupe_key', $dedupeKey)->first();

        if ($existing !== null) {
            return new IngestResult(
                sessionId: (int) $existing->radius_session_id,
                sampleId: (int) $existing->id,
                duplicate: true,
                deltaValid: (bool) $existing->delta_valid,
                outOfOrder: (bool) $existing->out_of_order,
                counterReset: (bool) $existing->counter_reset_detected,
            );
        }

        try {
            return DB::transaction(fn () => $this->store($event, $inputTotal, $outputTotal, $dedupeKey));
        } catch (QueryException $exception) {
            if (! $this->isUniqueViolation($exception)) {
                throw $exception;
            }

            // Concurrent duplicate delivery: return the stored sample.
            $sample = RadiusAccountingSample::query()->where('dedupe_key', $dedupeKey)->firstOrFail();

            return new IngestResult(
                sessionId: (int) $sample->radius_session_id,
                sampleId: (int) $sample->id,
                duplicate: true,
                deltaValid: (bool) $sample->delta_valid,
                outOfOrder: (bool) $sample->out_of_order,
                counterReset: (bool) $sample->counter_reset_detected,
            );
        }
    }

    private function store(AccountingEvent $event, int $inputTotal, int $outputTotal, string $dedupeKey): IngestResult
    {
        $session = $this->resolveSession($event);
        $inOrder = $session->last_sample_at === null || $event->sampledAt->greaterThanOrEqualTo($session->last_sample_at);

        $currentInput = (int) $session->input_bytes_64;
        $currentOutput = (int) $session->output_bytes_64;

        $inputDelta = 0;
        $outputDelta = 0;
        $deltaValid = false;
        $outOfOrder = false;
        $counterReset = false;

        if (! $inOrder) {
            // Keep the raw snapshot; do not corrupt the cumulative baseline.
            $outOfOrder = true;
            $deltaValid = false;
        } else {
            $inputDeltaResult = CounterNormalizer::delta($currentInput, $inputTotal);
            $outputDeltaResult = CounterNormalizer::delta($currentOutput, $outputTotal);

            $deltaValid = $inputDeltaResult['valid'] && $outputDeltaResult['valid'];
            $counterReset = ! $inputDeltaResult['valid'] || ! $outputDeltaResult['valid'];

            $inputDelta = $deltaValid ? $inputDeltaResult['delta'] : 0;
            $outputDelta = $deltaValid ? $outputDeltaResult['delta'] : 0;

            $session->input_bytes_64 = $inputTotal;
            $session->output_bytes_64 = $outputTotal;
            $session->last_sample_at = $event->sampledAt;
            $session->framed_ip_address = $event->framedIp ?? $session->framed_ip_address;
            $session->username = $event->username !== '' ? $event->username : $session->username;

            if ($event->eventType === AccountingEvent::STOP) {
                $session->status = RadiusSession::CLOSED;
                $session->stop_at = $event->sampledAt;
                $session->terminate_cause = $event->terminateCause;
                if ($event->sessionSeconds !== null) {
                    $session->session_seconds = $event->sessionSeconds;
                }
            }

            $session->save();
        }

        $sample = RadiusAccountingSample::query()->create([
            'radius_session_id' => $session->id,
            'nas_device_id' => $event->nasDeviceId,
            'username' => $event->username,
            'session_key' => $session->sessionKey(),
            'event_type' => $event->eventType,
            'sampled_at' => $event->sampledAt,
            'input_total_bytes' => $inputTotal,
            'output_total_bytes' => $outputTotal,
            'input_delta_bytes' => $inputDelta,
            'output_delta_bytes' => $outputDelta,
            'delta_valid' => $deltaValid,
            'counter_reset_detected' => $counterReset,
            'out_of_order' => $outOfOrder,
            'dedupe_key' => $dedupeKey,
            'source_event_id' => $event->sourceEventId,
            'ingested_at' => CarbonImmutable::now('UTC'),
        ]);

        NasDevice::query()->whereKey($event->nasDeviceId)->update(['last_seen_at' => $event->sampledAt]);

        return new IngestResult(
            sessionId: (int) $session->id,
            sampleId: (int) $sample->id,
            duplicate: false,
            deltaValid: $deltaValid,
            outOfOrder: $outOfOrder,
            counterReset: $counterReset,
        );
    }

    private function resolveSession(AccountingEvent $event): RadiusSession
    {
        $startAt = $event->startAt->setTimezone('UTC');

        $session = RadiusSession::query()
            ->where('nas_device_id', $event->nasDeviceId)
            ->where('acct_session_id', $event->sessionId)
            ->where('start_at', $startAt->format('Y-m-d H:i:s'))
            ->first();

        if ($session !== null) {
            return $session;
        }

        // Interim/Stop may arrive with a slightly different Acct-Start-Time;
        // attach them to the open session for this NAS/session id instead of
        // creating a duplicate session.
        if ($event->eventType !== AccountingEvent::START) {
            $open = RadiusSession::query()
                ->where('nas_device_id', $event->nasDeviceId)
                ->where('acct_session_id', $event->sessionId)
                ->whereIn('status', [RadiusSession::ACTIVE, RadiusSession::STALE])
                ->orderByDesc('start_at')
                ->first();

            if ($open !== null) {
                return $open;
            }
        }

        $assignment = $this->assignmentFor($event);
        $attributes = [
            'nas_device_id' => $event->nasDeviceId,
            'subscription_id' => $assignment?->subscription_id,
            'customer_id' => $assignment?->customer_id,
            'partner_id' => $assignment?->partner_id,
            'username' => $event->username,
            'acct_session_id' => $event->sessionId,
            'acct_unique_id' => $event->uniqueId,
            'framed_ip_address' => $event->framedIp,
            'start_at' => $startAt,
            'status' => RadiusSession::ACTIVE,
            'raw_record_reference' => $event->rawRecordReference,
        ];

        // Start packet (or orphan interim): a reused session id with a new
        // start time is a genuinely new session row.
        return RadiusSession::query()->create($attributes);
    }

    private function assignmentFor(AccountingEvent $event): ?NetworkAccessAssignment
    {
        if ($event->username === '') {
            return null;
        }

        return NetworkAccessAssignment::query()
            ->where('pppoe_username', $event->username)
            ->first();
    }

    private function dedupeKey(AccountingEvent $event, int $inputTotal, int $outputTotal): string
    {
        return hash('sha256', implode('|', [
            $event->nasDeviceId,
            $event->sessionId,
            $event->uniqueId ?? '-',
            $event->eventType,
            $event->sampledAt->getTimestamp(),
            $inputTotal,
            $outputTotal,
        ]));
    }

    private function isUniqueViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;

        // 23505 PostgreSQL, 23000 MySQL, 19 SQLite constraint.
        return in_array($sqlState, ['23505', '23000', '19'], true)
            || str_contains(strtolower($exception->getMessage()), 'unique');
    }
}
