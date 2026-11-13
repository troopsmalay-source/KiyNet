<?php

namespace App\Domain\Network\Gateways;

use App\Domain\Network\Contracts\NetworkAccessGateway;
use App\Domain\Network\RouterOs\UnavailableRouterOsClient;

final class NetworkGatewayFactory
{
    public static function make(): NetworkAccessGateway
    {
        if (config('network.driver') === 'radius') {
            return new RadiusSqlProjectionGateway(new UnavailableRouterOsClient());
        }

        return new FakeNetworkAccessGateway();
    }
}
