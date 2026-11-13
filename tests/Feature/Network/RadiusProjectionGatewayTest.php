<?php

namespace Tests\Feature\Network;

use App\Domain\Network\Dto\NetworkAccessCommand;
use App\Domain\Network\Dto\NetworkDisconnectCommand;
use App\Domain\Network\Exceptions\NetworkCommandPending;
use App\Domain\Network\Exceptions\NetworkProjectionUnavailable;
use App\Domain\Network\Gateways\RadiusSqlProjectionGateway;
use App\Domain\Network\Models\NetworkAccessProfile;
use App\Domain\Network\RouterOs\UnavailableRouterOsClient;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RadiusProjectionGatewayTest extends NetworkTestCase
{
    private RadiusSqlProjectionGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.radius_test' => [
                'driver' => 'sqlite',
                'database' => $this->databasePath,
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
            'network.driver' => 'radius',
            'network.radius.connection' => 'radius_test',
        ]);

        DB::purge('radius_test');

        Schema::connection('radius_test')->create('radcheck', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username', 64)->default('');
            $table->string('attribute', 64)->default('');
            $table->string('op', 2)->default('==');
            $table->string('value', 253)->default('');
        });

        Schema::connection('radius_test')->create('radreply', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username', 64)->default('');
            $table->string('attribute', 64)->default('');
            $table->string('op', 2)->default('=');
            $table->string('value', 253)->default('');
        });

        NetworkAccessProfile::query()->create([
            'code' => 'PROF-10-20',
            'name' => '10 up / 20 down',
            'download_mbps' => 20,
            'upload_mbps' => 10,
        ]);

        $this->gateway = new RadiusSqlProjectionGateway(new UnavailableRouterOsClient());
    }

    private function command(): NetworkAccessCommand
    {
        return new NetworkAccessCommand(
            subscriptionId: 501,
            customerId: 1,
            partnerId: null,
            username: 'pppoe-0001',
            nasDeviceId: null,
            profileCode: 'PROF-10-20',
            policyVersion: 1,
            desiredState: 'active',
            correlationId: 'corr-1',
            idempotencyKey: 'idem-1',
        );
    }

    public function test_provision_writes_the_speed_profile_and_clears_reject(): void
    {
        $this->gateway->provision($this->command());

        $reply = DB::connection('radius_test')->table('radreply')->where('username', 'pppoe-0001')->first();

        $this->assertNotNull($reply);
        $this->assertSame('Mikrotik-Rate-Limit', $reply->attribute);
        $this->assertSame('10M/20M', $reply->value);
        $this->assertFalse(
            DB::connection('radius_test')->table('radcheck')->where('username', 'pppoe-0001')->exists(),
        );
    }

    public function test_suspend_and_restore_manage_the_auth_type_reject_marker(): void
    {
        $this->gateway->suspend($this->command());

        $reject = DB::connection('radius_test')->table('radcheck')->where('username', 'pppoe-0001')->first();

        $this->assertNotNull($reject);
        $this->assertSame('Auth-Type', $reject->attribute);
        $this->assertSame('Reject', $reject->value);

        $this->gateway->restore($this->command());

        $this->assertFalse(
            DB::connection('radius_test')->table('radcheck')->where('username', 'pppoe-0001')->exists(),
        );
    }

    public function test_change_profile_updates_the_rate_limit_without_duplicating_rows(): void
    {
        $this->gateway->provision($this->command());
        $this->gateway->changeProfile($this->command());
        $this->gateway->changeProfile($this->command());

        $this->assertSame(
            1,
            DB::connection('radius_test')->table('radreply')
                ->where('username', 'pppoe-0001')
                ->where('attribute', 'Mikrotik-Rate-Limit')
                ->count(),
        );
    }

    public function test_disconnect_stays_pending_because_routeros_api_is_not_verified(): void
    {
        $this->expectException(NetworkCommandPending::class);

        $this->gateway->disconnect(new NetworkDisconnectCommand(
            sessionId: 1,
            username: 'pppoe-0001',
            nasDeviceId: null,
            reasonCode: 'OPERATOR_REQUEST',
            reasonNote: null,
            correlationId: 'corr-1',
            idempotencyKey: 'idem-1',
        ));
    }

    public function test_projection_refuses_to_write_in_fake_driver_mode(): void
    {
        config(['network.driver' => 'fake']);

        $this->expectException(NetworkProjectionUnavailable::class);

        $this->gateway->provision($this->command());
    }
}
