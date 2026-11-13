<?php

namespace Tests\Feature\Network;

use App\Domain\Network\Contracts\NetworkAccessGateway;
use App\Domain\Network\Dto\NetworkAccessCommand;
use App\Domain\Network\Dto\NetworkAccessQuery;
use App\Domain\Network\Dto\NetworkAccessStatus;
use App\Domain\Network\Dto\NetworkCommandResult;
use App\Domain\Network\Dto\NetworkDisconnectCommand;
use App\Domain\Network\Exceptions\IdempotencyConflict;
use App\Domain\Network\Gateways\FakeNetworkAccessGateway;
use App\Domain\Network\Models\NasDevice;
use App\Domain\Network\Models\NetworkAccessAssignment;
use App\Domain\Network\Models\RadiusSyncOutbox;
use App\Domain\Network\Services\NetworkOutboxDispatcher;
use App\Domain\Network\Services\NetworkOutboxProcessor;
use Carbon\CarbonImmutable;
use RuntimeException;

class NetworkOutboxTest extends NetworkTestCase
{
    private NetworkAccessAssignment $assignment;

    private NetworkOutboxDispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        FakeNetworkAccessGateway::reset();

        $nas = NasDevice::query()->create([
            'code' => 'NAS-01',
            'name' => 'NAS Lab 01',
            'management_ip' => '10.10.0.1',
        ]);

        $partnerId = $this->makeMitra();
        $customerId = $this->makeCustomer($partnerId);

        $this->assignment = NetworkAccessAssignment::query()->create([
            'subscription_id' => 501,
            'customer_id' => $customerId,
            'partner_id' => $partnerId,
            'nas_device_id' => $nas->id,
            'pppoe_username' => 'pppoe-0001',
            'desired_state' => 'active',
            'policy_version' => 1,
        ]);

        $this->dispatcher = new NetworkOutboxDispatcher();
    }

    private function command(string $key = 'idem-1', int $policyVersion = 1, string $username = 'pppoe-0001'): NetworkAccessCommand
    {
        return new NetworkAccessCommand(
            subscriptionId: 501,
            customerId: $this->assignment->customer_id,
            partnerId: $this->assignment->partner_id,
            username: $username,
            nasDeviceId: $this->assignment->nas_device_id,
            profileCode: 'PROF-10M',
            policyVersion: $policyVersion,
            desiredState: 'active',
            correlationId: 'corr-1',
            idempotencyKey: $key,
        );
    }

    public function test_same_key_and_payload_returns_the_same_command(): void
    {
        $first = $this->dispatcher->enqueueAccessCommand($this->command());
        $second = $this->dispatcher->enqueueAccessCommand($this->command());

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, RadiusSyncOutbox::query()->count());
    }

    public function test_same_key_with_different_payload_is_a_conflict(): void
    {
        $this->dispatcher->enqueueAccessCommand($this->command());

        $this->expectException(IdempotencyConflict::class);

        $this->dispatcher->enqueueAccessCommand($this->command(username: 'pppoe-other'));
    }

    public function test_successful_command_updates_assignment_and_is_audited(): void
    {
        $command = $this->dispatcher->enqueueAccessCommand($this->command());

        $processed = (new NetworkOutboxProcessor(new FakeNetworkAccessGateway()))->process($command);

        $this->assertSame(RadiusSyncOutbox::SUCCEEDED, $processed->status);
        $this->assertNotNull($processed->processed_at);
        $this->assertSame('active', $this->assignment->refresh()->applied_state);
        $this->assertSame(1, \App\Domain\Network\Models\NetworkPolicyAudit::query()->count());
        $this->assertCount(1, FakeNetworkAccessGateway::applied());
    }

    public function test_stale_policy_version_is_superseded_and_not_applied(): void
    {
        $stale = $this->dispatcher->enqueueAccessCommand($this->command('idem-1', 1));
        $this->dispatcher->enqueueAccessCommand($this->command('idem-2', 2));

        $processed = (new NetworkOutboxProcessor(new FakeNetworkAccessGateway()))->process($stale);

        $this->assertSame(RadiusSyncOutbox::SUPERSEDED, $processed->status);
        $this->assertCount(0, FakeNetworkAccessGateway::applied());
    }

    public function test_transient_failure_retries_then_dead_letters(): void
    {
        config([
            'network.outbox.max_attempts' => 2,
            'network.outbox.retry_base_seconds' => 0,
            'network.outbox.retry_max_seconds' => 0,
        ]);

        $failing = new class implements NetworkAccessGateway
        {
            public function provision(NetworkAccessCommand $command): NetworkCommandResult
            {
                throw new RuntimeException('RouterOS API unreachable');
            }

            public function suspend(NetworkAccessCommand $command): NetworkCommandResult
            {
                throw new RuntimeException('RouterOS API unreachable');
            }

            public function restore(NetworkAccessCommand $command): NetworkCommandResult
            {
                throw new RuntimeException('RouterOS API unreachable');
            }

            public function changeProfile(NetworkAccessCommand $command): NetworkCommandResult
            {
                throw new RuntimeException('RouterOS API unreachable');
            }

            public function disconnect(NetworkDisconnectCommand $command): NetworkCommandResult
            {
                throw new RuntimeException('RouterOS API unreachable');
            }

            public function getStatus(NetworkAccessQuery $query): NetworkAccessStatus
            {
                throw new RuntimeException('RouterOS API unreachable');
            }
        };

        $command = $this->dispatcher->enqueueAccessCommand($this->command());
        $processor = new NetworkOutboxProcessor($failing);

        $first = $processor->process($command);
        $this->assertSame(RadiusSyncOutbox::FAILED, $first->status);
        $this->assertSame(1, (int) $first->attempts);
        $this->assertStringContainsString('RouterOS API unreachable', (string) $first->last_error);

        $first->available_at = CarbonImmutable::now('UTC');
        $first->save();

        $second = $processor->process($first->refresh());

        $this->assertSame(RadiusSyncOutbox::DEAD_LETTER, $second->status);
        $this->assertSame(2, (int) $second->attempts);
        $this->assertNull($this->assignment->refresh()->applied_state);
    }

    public function test_disconnect_is_queued_as_a_session_command(): void
    {
        $outbox = $this->dispatcher->enqueueDisconnect(new NetworkDisconnectCommand(
            sessionId: 77,
            username: 'pppoe-0001',
            nasDeviceId: (int) $this->assignment->nas_device_id,
            reasonCode: 'OPERATOR_REQUEST',
            reasonNote: null,
            correlationId: 'corr-1',
            idempotencyKey: 'disconnect-77',
        ));

        $this->assertSame('session', $outbox->aggregate_type);
        $this->assertSame('disconnect', $outbox->command_type);
        $this->assertArrayNotHasKey('password', (array) $outbox->payload_json);
    }

    public function test_stale_processing_lock_is_reclaimed(): void
    {
        $command = $this->dispatcher->enqueueAccessCommand($this->command());
        $command->status = RadiusSyncOutbox::PROCESSING;
        $command->locked_at = CarbonImmutable::now('UTC')->subHour();
        $command->save();

        $reclaimed = (new NetworkOutboxProcessor(new FakeNetworkAccessGateway()))->reclaimStaleLocks();

        $this->assertSame(1, $reclaimed);
        $this->assertSame(RadiusSyncOutbox::PENDING, $command->refresh()->status);
        $this->assertNull($command->locked_at);
    }
}
