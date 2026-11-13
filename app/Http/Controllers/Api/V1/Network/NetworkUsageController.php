<?php

namespace App\Http\Controllers\Api\V1\Network;

use App\Domain\Accounting\Readers\EloquentNetworkAnalyticsReader;
use App\Domain\Network\Dto\CustomerUsageQuery;
use App\Domain\Network\Dto\PartnerUsageQuery;
use App\Domain\Network\Dto\ThroughputQuery;
use App\Domain\Network\Services\NetworkAuthorizer;
use App\Domain\Network\Support\ApiEnvelope;
use App\Domain\Network\Support\ResolvesNetworkUser;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NetworkUsageController extends Controller
{
    use ResolvesNetworkUser;

    public function __construct(
        private readonly NetworkAuthorizer $authorizer,
        private readonly EloquentNetworkAnalyticsReader $reader,
    ) {
    }

    public function customer(Request $request, int $customer): JsonResponse
    {
        $scope = $this->authorizer->scopeFor($this->networkUser($request));
        [$from, $to] = $this->range($request);

        $summary = $this->reader->customerUsage(new CustomerUsageQuery($scope, $customer, $from, $to));

        return ApiEnvelope::ok($summary->toArray());
    }

    public function partner(Request $request, int $partner): JsonResponse
    {
        $scope = $this->authorizer->scopeFor($this->networkUser($request));
        [$from, $to] = $this->range($request);

        $summary = $this->reader->partnerUsage(new PartnerUsageQuery($scope, $partner, $from, $to));

        return ApiEnvelope::ok($summary->toArray());
    }

    public function throughput(Request $request, int $partner): JsonResponse
    {
        $scope = $this->authorizer->scopeFor($this->networkUser($request));
        [$from, $to] = $this->range($request);

        $series = $this->reader->throughputSeries(new ThroughputQuery($scope, $partner, $from, $to));

        return ApiEnvelope::ok($series->toArray());
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function range(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $from = CarbonImmutable::parse($validated['from'])->utc()->startOfDay();
        $to = CarbonImmutable::parse($validated['to'])->utc()->startOfDay();

        if ($from->diffInDays($to) > 366) {
            abort(422, 'Rentang maksimal 366 hari.');
        }

        return [$from, $to];
    }
}
