<?php

namespace App\Domain\Network\Contracts;

use App\Domain\Network\Dto\NetworkAccessCommand;
use App\Domain\Network\Dto\NetworkAccessQuery;
use App\Domain\Network\Dto\NetworkAccessStatus;
use App\Domain\Network\Dto\NetworkCommandResult;
use App\Domain\Network\Dto\NetworkDisconnectCommand;

/**
 * Frozen boundary between the business application and network infrastructure.
 * Agent A consumes this interface only; Agent B owns the implementations.
 */
interface NetworkAccessGateway
{
    public function provision(NetworkAccessCommand $command): NetworkCommandResult;

    public function suspend(NetworkAccessCommand $command): NetworkCommandResult;

    public function restore(NetworkAccessCommand $command): NetworkCommandResult;

    public function changeProfile(NetworkAccessCommand $command): NetworkCommandResult;

    public function disconnect(NetworkDisconnectCommand $command): NetworkCommandResult;

    public function getStatus(NetworkAccessQuery $query): NetworkAccessStatus;
}
