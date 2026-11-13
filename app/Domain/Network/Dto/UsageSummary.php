<?php

namespace App\Domain\Network\Dto;

use Carbon\CarbonInterface;

final class UsageSummary
{
    /**
     * @param  array<int, array{usage_date: string, upload_bytes: int, download_bytes: int, total_bytes: int, completeness: string}>  $daily
     */
    public function __construct(
        public readonly string $scopeType,
        public readonly int $scopeId,
        public readonly CarbonInterface $from,
        public readonly CarbonInterface $to,
        public readonly int $uploadBytes,
        public readonly int $downloadBytes,
        public readonly int $totalBytes,
        public readonly ?float $peakDownloadMbps,
        public readonly ?float $peakUploadMbps,
        public readonly string $completeness,
        public readonly array $daily = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'scope_type' => $this->scopeType,
            'scope_id' => (string) $this->scopeId,
            'from' => $this->from->toDateString(),
            'to' => $this->to->toDateString(),
            'upload_bytes' => $this->uploadBytes,
            'download_bytes' => $this->downloadBytes,
            'total_bytes' => $this->totalBytes,
            'peak_download_mbps' => $this->peakDownloadMbps,
            'peak_upload_mbps' => $this->peakUploadMbps,
            'completeness' => $this->completeness,
            'daily' => $this->daily,
        ];
    }
}
