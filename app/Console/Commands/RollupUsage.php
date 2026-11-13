<?php

namespace App\Console\Commands;

use App\Domain\Accounting\Models\CustomerUsageDaily;
use App\Domain\Accounting\Models\PartnerUsageDaily;
use App\Domain\Accounting\Services\UsageRollupService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class RollupUsage extends Command
{
    protected $signature = 'network:rollup-usage
                            {--date= : Target date (Y-m-d), default yesterday}
                            {--customer=* : Customer ids to roll up}
                            {--partner=* : Partner ids to roll up}';

    protected $description = 'Build customer/partner daily usage aggregates from accounting samples';

    public function handle(UsageRollupService $rollup): int
    {
        $date = $this->option('date') !== null
            ? CarbonImmutable::parse((string) $this->option('date'))
            : CarbonImmutable::yesterday('UTC');

        $customers = $this->option('customer');
        $partners = $this->option('partner');

        if ($customers === [] && $partners === []) {
            $customers = CustomerUsageDaily::query()
                ->whereDate('usage_date', $date->toDateString())
                ->pluck('customer_id')
                ->all();
            $partners = PartnerUsageDaily::query()
                ->whereDate('usage_date', $date->toDateString())
                ->pluck('partner_id')
                ->all();
        }

        $done = 0;
        foreach ($customers as $customerId) {
            $rollup->rollupCustomerDay((int) $customerId, $date) !== null && $done++;
        }
        foreach ($partners as $partnerId) {
            $rollup->rollupPartnerDay((int) $partnerId, $date) !== null && $done++;
        }

        $this->info('network:rollup-usage date='.$date->toDateString()." rolled_up={$done}");

        return self::SUCCESS;
    }
}
