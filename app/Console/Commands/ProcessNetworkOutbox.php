<?php

namespace App\Console\Commands;

use App\Domain\Network\Services\NetworkOutboxProcessor;
use Illuminate\Console\Command;

class ProcessNetworkOutbox extends Command
{
    protected $signature = 'network:outbox
                            {--limit=100 : Maximum commands per run}';

    protected $description = 'Reclaim stale locks and process pending radius_sync_outbox commands';

    public function handle(NetworkOutboxProcessor $processor): int
    {
        $reclaimed = $processor->reclaimStaleLocks();
        $processed = $processor->processPending(max(1, (int) $this->option('limit')));

        $this->info("network:outbox reclaimed={$reclaimed} processed={$processed}");

        return self::SUCCESS;
    }
}
