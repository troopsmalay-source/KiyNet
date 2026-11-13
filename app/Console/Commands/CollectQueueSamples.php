<?php

namespace App\Console\Commands;

use App\Domain\Accounting\Services\QueueSamplingCollector;
use App\Domain\Network\Models\NasDevice;
use Illuminate\Console\Command;

class CollectQueueSamples extends Command
{
    protected $signature = 'network:sample-queues
                            {--nas=* : NAS device ids (default: all active with accounting)}';

    protected $description = 'Sample per-partner aggregate queue counters into router_queue_samples';

    public function handle(QueueSamplingCollector $collector): int
    {
        $nasIds = $this->option('nas');

        $query = NasDevice::query()->where('status', 'active')->where('accounting_enabled', true);
        if ($nasIds !== []) {
            $query->whereIn('id', array_map('intval', $nasIds));
        }

        $stored = 0;

        foreach ($query->get() as $nas) {
            $stored += $collector->collectFromRouter((int) $nas->id);
        }

        $this->info("network:sample-queues stored={$stored}");

        return self::SUCCESS;
    }
}
