<?php

namespace Tests\Feature\Api;

use App\Models\Mitra;
use App\Models\Pelanggan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 2: partner/customer business core must be tenant isolated.
 * Tenant authority always comes from the authenticated principal.
 */
class BusinessTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Mitra $partnerA;

    private Mitra $partnerB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partnerA = Mitra::factory()->create(['nama_mitra' => 'Mitra A']);
        $this->partnerB = Mitra::factory()->create(['nama_mitra' => 'Mitra B']);
    }

    public function test_central_admin_lists_all_partners(): void
    {
        $admin = User::factory()->withRole('admin')->create();

        $this->actingAs($admin)
            ->getJson('/api/v1/partners')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data', 'meta' => ['request_id', 'page', 'per_page', 'total', 'last_page']]);
    }

    public function test_partner_user_only_lists_own_partner(): void
    {
        $user = User::factory()->withRole('mitra')->forMitra($this->partnerA)->create();

        $this->actingAs($user)
            ->getJson('/api/v1/partners')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->partnerA->id);
    }

    public function test_partner_cannot_view_another_tenant(): void
    {
        $user = User::factory()->withRole('mitra')->forMitra($this->partnerA)->create();

        $this->actingAs($user)
            ->getJson('/api/v1/partners/'.$this->partnerB->id)
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }

    public function test_request_supplied_partner_id_cannot_place_customer_in_another_tenant(): void
    {
        $user = User::factory()->withRole('mitra')->forMitra($this->partnerA)->create();

        $response = $this->actingAs($user)->postJson('/api/v1/customers', [
            'mitra_id' => $this->partnerB->id,
            'nama_pelanggan' => 'Pelanggan Uji',
            'no_hp' => '081200000001',
            'alamat_pasang' => 'Jalan Uji 1',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.partner_id', $this->partnerA->id);

        $this->assertDatabaseHas('pelanggan', [
            'nama_pelanggan' => 'Pelanggan Uji',
            'mitra_id' => $this->partnerA->id,
        ]);
    }

    public function test_partner_cannot_move_own_customer_to_another_tenant(): void
    {
        $user = User::factory()->withRole('mitra')->forMitra($this->partnerA)->create();
        $customer = $this->customer($this->partnerA);

        $this->actingAs($user)
            ->patchJson('/api/v1/customers/'.$customer->id, [
                'mitra_id' => $this->partnerB->id,
                'nama_pelanggan' => 'Nama Baru',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.partner_id', $this->partnerA->id);

        $this->assertSame($this->partnerA->id, $customer->fresh()->mitra_id);
    }

    public function test_central_admin_can_transfer_customer_between_partners(): void
    {
        $admin = User::factory()->withRole('admin')->create();
        $customer = $this->customer($this->partnerA);

        $this->actingAs($admin)
            ->patchJson('/api/v1/customers/'.$customer->id, [
                'mitra_id' => $this->partnerB->id,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.partner_id', $this->partnerB->id);

        $this->assertSame($this->partnerB->id, $customer->fresh()->mitra_id);
    }

    public function test_partner_cannot_create_partner(): void
    {
        $user = User::factory()->withRole('mitra')->forMitra($this->partnerA)->create();

        $this->actingAs($user)
            ->postJson('/api/v1/partners', [
                'nama_mitra' => 'Mitra Baru',
                'status' => 'aktif',
            ])
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_customer_validation_uses_frozen_envelope(): void
    {
        $user = User::factory()->withRole('mitra')->forMitra($this->partnerA)->create();

        $this->actingAs($user)
            ->postJson('/api/v1/customers', ['no_hp' => '0812'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['error' => ['code', 'message', 'details' => ['nama_pelanggan']], 'meta' => ['request_id']]);
    }

    private function customer(Mitra $mitra): Pelanggan
    {
        return Pelanggan::create([
            'mitra_id' => $mitra->id,
            'kode_pelanggan' => 'KN-TEST-'.fake()->unique()->numerify('####'),
            'nama_pelanggan' => 'Pelanggan '.fake()->lastName(),
            'no_hp' => '081200000002',
            'alamat_pasang' => 'Jalan Uji 2',
            'status' => 'aktif',
        ]);
    }
}
