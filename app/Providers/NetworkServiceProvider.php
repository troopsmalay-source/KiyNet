<?php

namespace App\Providers;

use App\Domain\Accounting\Readers\EloquentNetworkAnalyticsReader;
use App\Domain\Network\Contracts\NetworkAccessGateway;
use App\Domain\Network\Contracts\NetworkAnalyticsReader;
use App\Domain\Network\Gateways\NetworkGatewayFactory;
use App\Domain\Network\Services\NetworkOutboxDispatcher;
use App\Domain\Network\Services\SubscriptionNetworkSync;
use Illuminate\Support\ServiceProvider;

/**
 * Agent B owned bindings.
 *
 * Registration requires one line in bootstrap/providers.php, which is Agent A
 * owned. Until that merge, controllers type-hint the concrete reader/gateway
 * services and tests register this provider explicitly.
 */
class NetworkServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NetworkAccessGateway::class, fn () => NetworkGatewayFactory::make());
        $this->app->singleton(NetworkAnalyticsReader::class, fn () => new EloquentNetworkAnalyticsReader());
        $this->app->singleton(SubscriptionNetworkSync::class);
    }
}
