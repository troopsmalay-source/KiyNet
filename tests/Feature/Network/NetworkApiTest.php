<?php

namespace Tests\Feature\Network;

use App\Domain\Accounting\Models\CustomerUsageDaily;
use App\Domain\Accounting\Models\RadiusSession;
use App\Domain\Network\Models\NasDevice;
use App\Domain\Network\Models\RadiusSyncOutbox;
use Carbon\CarbonImmutable;

class NetworkApiTest extends NetworkTestCase
{
    private int $partnerA;

    private int $partnerB;

    private int $customerA;

    private int $customerB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partnerA = $this->makeMitra('MTR-A');
        $this->partnerB = $this->makeMitra('MTR-B');
        $this->customerA = $this->makeCustomer($this->partnerA, 'C-A');
        $this->customerB = $this->makeCustomer($this->partnerB, 'C-B');

        CustomerUsageDaily::query()->create([
            'customer_id' => $this->customerA,
            'partner_id' => $this->partnerA,
            'usage_date' => '2026-11-12',
            'upload_bytes' => 100,
            'download_bytes' => 900,
            'total_bytes' => 1000,
            'completeness' => CustomerUsageDaily::COMPLETE,
            'calculation_version' => 1,
            'calculated_at' => CarbonImmutable::now('UTC'),
        ]);

        CustomerUsageDaily::query()->create([
            'customer_id' => $this->customerB,
            'partner_id' => $this->partnerB,
            'usage_date' => '2026-11-12',
            'upload_bytes' => 5000,
            'download_bytes' => 5000,
            'total_bytes' => 10000,
            'completeness' => CustomerUsageDaily::COMPLETE,
            'calculation_version' => 1,
            'calculated_at' => CarbonImmutable::now('UTC'),
        ]);
    }

    private function nas(): NasDevice
    {
        return NasDevice::query()->create([
            'code' => 'NAS-01',
            'name' => 'NAS Lab 01',
            'management_ip' => '10.10.0.1',
        ]);
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/v1/network/sessions')->assertStatus(401);
    }

    public function test_partner_reads_own_customer_usage_but_not_another_tenants(): void
    {
        $user = $this->makeUser('mitra', $this->partnerA);

        $this->actingAs($user)
            ->getJson('/api/v1/network/customers/'.$this->customerA.'/usage?from=2026-11-01&to=2026-11-30')
            ->assertOk()
            ->assertJsonPath('data.total_bytes', 1000)
            ->assertJsonPath('data.scope_type', 'customer');

        $this->actingAs($user)
            ->getJson('/api/v1/network/customers/'.$this->customerB.'/usage?from=2026-11-01&to=2026-11-30')
            ->assertNotFound();
    }

    public function test_partner_usage_endpoint_is_scoped_to_the_authenticated_tenant(): void
    {
        $user = $this->makeUser('mitra', $this->partnerA);

        $this->actingAs($user)
            ->getJson('/api/v1/network/partners/'.$this->partnerA.'/usage?from=2026-11-01&to=2026-11-30')
            ->assertOk()
            ->assertJsonPath('data.total_bytes', 1000);

        $this->actingAs($user)
            ->getJson('/api/v1/network/partners/'.$this->partnerB.'/usage?from=2026-11-01&to=2026-11-30')
            ->assertNotFound();
    }

    public function test_partner_cannot_disconnect_sessions(): void
    {
        $session = RadiusSession::query()->create([
            'nas_device_id' => $this->nas()->id,
            'customer_id' => $this->customerA,
            'partner_id' => $this->partnerA,
            'username' => 'pppoe-0001',
            'acct_session_id' => 'S-1',
            'start_at' => CarbonImmutable::now('UTC'),
            'status' => RadiusSession::ACTIVE,
        ]);

        $this->actingAs($this->makeUser('mitra', $this->partnerA))
            ->postJson('/api/v1/network/sessions/'.$session->id.'/disconnect', ['reason_code' => 'TEST'])
            ->assertForbidden();
    }

    public function test_central_operator_queues_a_disconnect_command(): void
    {
        $session = RadiusSession::query()->create([
            'nas_device_id' => $this->nas()->id,
            'customer_id' => $this->customerA,
            'partner_id' => $this->partnerA,
            'username' => 'pppoe-0001',
            'acct_session_id' => 'S-1',
            'start_at' => CarbonImmutable::now('UTC'),
            'status' => RadiusSession::ACTIVE,
        ]);

        $this->actingAs($this->makeUser('admin'))
            ->postJson('/api/v1/network/sessions/'.$session->id.'/disconnect', [
                'reason_code' => 'OPERATOR_REQUEST',
                'reason_note' => 'Uji lab',
            ])
            ->assertStatus(202)
            ->assertJsonPath('data.sync_status', 'pending');

        $this->assertSame(1, RadiusSyncOutbox::query()->where('command_type', 'disconnect')->count());
    }

    public function test_sync_commands_are_central_only(): void
    {
        $this->actingAs($this->makeUser('admin'))
            ->getJson('/api/v1/network/sync-commands')
            ->assertOk();

        $this->actingAs($this->makeUser('mitra', $this->partnerA))
            ->getJson('/api/v1/network/sync-commands')
            ->assertForbidden();
    }

    public function test_nas_registration_accepts_references_only_and_never_returns_them(): void
    {
        $response = $this->actingAs($this->makeUser('admin'))
            ->postJson('/api/v1/network/nas', [
                'code' => 'NAS-02',
                'name' => 'NAS Pop 2',
                'management_ip' => '10.10.0.2',
                'radius_secret_reference' => 'vault://radius/nas-02',
                'api_secret_reference' => 'vault://routeros/nas-02',
                'accounting_enabled' => true,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.has_radius_secret', true);

        $this->assertArrayNotHasKey('radius_secret_reference', $response->json('data'));

        $this->assertDatabaseHas('nas_devices', [
            'code' => 'NAS-02',
            'radius_secret_reference' => 'vault://radius/nas-02',
        ]);
    }

    public function test_partner_cannot_register_a_nas(): void
    {
        $this->actingAs($this->makeUser('mitra', $this->partnerA))
            ->postJson('/api/v1/network/nas', [
                'code' => 'NAS-03',
                'name' => 'NAS Pop 3',
                'management_ip' => '10.10.0.3',
            ])
            ->assertForbidden();
    }

    public function test_central_usage_aggregates_all_partners(): void
    {
        $this->actingAs($this->makeUser('admin'))
            ->getJson('/api/v1/network/customers/'.$this->customerB.'/usage?from=2026-11-01&to=2026-11-30')
            ->assertOk()
            ->assertJsonPath('data.total_bytes', 10000);
    }
}
