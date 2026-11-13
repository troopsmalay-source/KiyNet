<?php

namespace App\Domain\Network\Dto;

use Carbon\CarbonInterface;

final class ThroughputSeries
{
    /**
     * @param  array<int, array{sampled_at: string, download_mbps: float, upload_mbps: float, sample_interval_seconds: int}>  $samples
     */
    public function __construct(
        public readonly int $partnerId,
        public readonly CarbonInterface $from,
        public readonly CarbonInterface $to,
        public readonly int $resolutionSeconds,
        public readonly array $samples,
        public readonly string $completeness,
    ) {
    }

    public function toArray(): array
    {
        return [
            'partner_id' => (string) $this->partnerId,
            'from' => $this->from->toDateString(),
            'to' => $this->to->toDateString(),
            'resolution_seconds' => $this->resolutionSeconds,
            'completeness' => $this->completeness,
            'samples' => $this->samples,
        ];
    }
}
