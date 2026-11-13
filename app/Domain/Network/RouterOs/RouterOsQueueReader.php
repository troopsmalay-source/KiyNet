<?php

namespace App\Domain\Network\RouterOs;

/**
 * Reads aggregate queue statistics per partner queue from a RouterOS NAS.
 *
 * Implementations return raw counters only; rate math and persistence belong
 * to the QueueSamplingCollector. Values are RouterOS "bytes" counters which
 * the collector normalizes.
 */
interface RouterOsQueueReader
{
    /**
     * @param  string  $queuePrefix  e.g. "partner-12-" matching the cap design
     * @return array<int, array{queue_key: string, bytes_down_total: int, bytes_up_total: int}>
     */
    public function queueCounters(string $queuePrefix): array;

    public function isEnabled(): bool;
}
