<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Models\RadiusSession;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * A missing Stop record is reconciled by marking the session stale/estimated.
 * Stop time and usage are never fabricated: the last observed sample remains
 * the newest evidence, and the rollup keeps completeness=estimated.
 */
final class StaleSessionReconciler
{
    public function reconcile(?CarbonInterface $now = null, ?int $nasDeviceId = null): int
    {
        $now = $now !== null ? CarbonImmutable::instance($now) : CarbonImmutable::now('UTC');
        $cutoff = $now->subMinutes((int) config('network.accounting.stale_after_minutes', 90));

        $query = RadiusSession::query()
            ->where('status', RadiusSession::ACTIVE)
            ->where(function ($builder) use ($cutoff) {
                $builder->where('last_sample_at', '<', $cutoff)
                    ->orWhere(function ($inner) use ($cutoff) {
                        $inner->whereNull('last_sample_at')->where('start_at', '<', $cutoff);
                    });
            });

        if ($nasDeviceId !== null) {
            $query->where('nas_device_id', $nasDeviceId);
        }

        $sessions = $query->get();
        $closed = 0;

        foreach ($sessions as $session) {
            $session->status = RadiusSession::STALE;
            $session->estimated_close = true;
            $session->close_reason = 'missing_stop';
            $session->save();
            $closed++;
        }

        return $closed;
    }
}
