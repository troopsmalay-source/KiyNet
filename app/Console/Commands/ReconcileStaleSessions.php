<?php

namespace App\Console\Commands;

use App\Domain\Accounting\Services\StaleSessionReconciler;
use Illuminate\Console\Command;

class ReconcileStaleSessions extends Command
{
    protected $signature = 'network:reconcile-stale
                            {--nas= : Restrict to one NAS device id}';

    protected $description = 'Mark RADIUS sessions without a Stop record as stale/estimated';

    public function handle(StaleSessionReconciler $reconciler): int
    {
        $closed = $reconciler->reconcile(
            nasDeviceId: $this->option('nas') !== null ? (int) $this->option('nas') : null,
        );

        $this->info("network:reconcile-stale stale_marked={$closed}");

        return self::SUCCESS;
    }
}
