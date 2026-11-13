<?php

namespace App\Domain\Network\Contracts;

use App\Domain\Network\Dto\CustomerUsageQuery;
use App\Domain\Network\Dto\PaginatedSessions;
use App\Domain\Network\Dto\PartnerUsageQuery;
use App\Domain\Network\Dto\SessionQuery;
use App\Domain\Network\Dto\ThroughputQuery;
use App\Domain\Network\Dto\ThroughputSeries;
use App\Domain\Network\Dto\UsageSummary;

interface NetworkAnalyticsReader
{
    public function customerUsage(CustomerUsageQuery $query): UsageSummary;

    public function partnerUsage(PartnerUsageQuery $query): UsageSummary;

    public function activeSessions(SessionQuery $query): PaginatedSessions;

    public function throughputSeries(ThroughputQuery $query): ThroughputSeries;
}
