<?php

namespace Tests\Feature\Api;

use App\Models\Langganan;
use App\Models\Mitra;
use App\Models\PaketInternet;
use App\Models\Pelanggan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2CatalogSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private Mitra $partnerA;

    private Mitra $partnerB;

    private PaketInternet $package20;

    private PaketInternet $package50;

    private User $admin;

    private User $partnerUser;

    private Pelanggan $customerA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partnerA = Mitra::factory()->create(['nama_mitra' => 'Mitra A']);
        $this->partnerB = Mitra::factory()->create(['nama_mitra' => 'Mitra B']);
        $this->package20 = PaketInternet::create([
            'kode_paket' => 'PK-T20', 'nama_paket' => 'Home 20',
            'download_mbps' => 20, 'upload_mbps' => 5, 'harga' => 150000, 'is_active' => true,
        ]);
        $this->package50 = PaketInternet::create([
            'kode_paket' => 'PK-T50', 'nama_paket' => 'Home 50',
            'download_mbps' => 50, 'upload_mbps' => 20, 'harga' => 300000, 'is_active' => true,
        ]);
        $this->admin = User::factory()->withRole('admin')->create();
        $this->partnerUser = User::factory()->withRole('mitra')->forMitra($this->partnerA)->create();
        $this->customerA = Pelanggan::create([
            'mitra_id' => $this->partnerA->id,
            'kode_pelanggan' => 'KN-SUB-001',
            'nama_pelanggan' => 'Pelanggan A',
            'no_hp' => '081200000010',
            'alamat_pasang' => 'Jalan A',
            'status' => 'aktif',
        ]);
    }

    public function test_central_admin_manages_packages_and_partner_cannot(): void
    {
        $this->actingAs($this->admin)->postJson('/api/v1/internet-packages', [
            'nama_paket' => 'Home 100',
            'download_mbps' => 100,
            'upload_mbps' => 50,
            'harga' => 450000,
        ])->assertStatus(201)->assertJsonPath('data.harga_idr', 450000);

        $this->actingAs($this->partnerUser)->postJson('/api/v1/internet-packages', [
            'nama_paket' => 'Illegal', 'harga' => 1000,
        ])->assertStatus(403)->assertJsonPath('error.code', 'FORBIDDEN');

        $this->actingAs($this->partnerUser)->getJson('/api/v1/internet-packages')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_partner_creates_subscription_only_for_own_customer(): void
    {
        $response = $this->actingAs($this->partnerUser)->postJson('/api/v1/subscriptions', [
            'customer_id' => $this->customerA->id,
            'package_id' => $this->package20->id,
            'start_date' => '2026-11-01',
            'billing_day' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'menunggu')
            ->assertJsonPath('data.package.harga_idr', 150000);

        $foreignCustomer = Pelanggan::create([
            'mitra_id' => $this->partnerB->id,
            'kode_pelanggan' => 'KN-SUB-002',
            'nama_pelanggan' => 'Pelanggan B',
            'no_hp' => '081200000011',
            'alamat_pasang' => 'Jalan B',
            'status' => 'aktif',
        ]);

        $this->actingAs($this->partnerUser)->postJson('/api/v1/subscriptions', [
            'customer_id' => $foreignCustomer->id,
            'package_id' => $this->package20->id,
            'start_date' => '2026-11-01',
        ])->assertStatus(404);
    }

    public function test_subscription_activation_suspend_restore_and_change_package(): void
    {
        $subscription = Langganan::create([
            'pelanggan_id' => $this->customerA->id,
            'paket_internet_id' => $this->package20->id,
            'tanggal_aktivasi' => '2026-11-01',
            'hari_aktivasi' => 1,
            'tanggal_jatuh_tempo' => '2026-12-01',
            'masa_tenggang_hari' => 7,
            'harga_snapshot' => 150000,
            'status' => 'menunggu',
        ]);

        $this->actingAs($this->admin)->postJson("/api/v1/subscriptions/{$subscription->id}/activate")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'aktif')
            ->assertJsonPath('data.network.sync_status', 'not_required');

        $this->actingAs($this->admin)->postJson("/api/v1/subscriptions/{$subscription->id}/suspend", [
            'reason_code' => 'INVOICE_OVERDUE',
        ])->assertStatus(200)->assertJsonPath('data.status', 'ditangguhkan');

        $this->actingAs($this->admin)->postJson("/api/v1/subscriptions/{$subscription->id}/restore")
            ->assertStatus(200)->assertJsonPath('data.status', 'aktif');

        $this->actingAs($this->admin)->postJson("/api/v1/subscriptions/{$subscription->id}/change-package", [
            'package_id' => $this->package50->id,
        ])->assertStatus(200)
            ->assertJsonPath('data.package.id', $this->package50->id)
            ->assertJsonPath('data.package.harga_idr', 300000);

        $this->assertDatabaseHas('subscription_events', ['langganan_id' => $subscription->id, 'type' => 'package_changed']);
        $this->assertSame(300000, (int) $subscription->fresh()->harga_snapshot);
    }

    public function test_invalid_subscription_transition_returns_conflict(): void
    {
        $subscription = Langganan::create([
            'pelanggan_id' => $this->customerA->id,
            'paket_internet_id' => $this->package20->id,
            'tanggal_aktivasi' => '2026-11-01',
            'hari_aktivasi' => 1,
            'masa_tenggang_hari' => 7,
            'harga_snapshot' => 150000,
            'status' => 'aktif',
        ]);

        $this->actingAs($this->admin)->postJson("/api/v1/subscriptions/{$subscription->id}/activate")
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'CONFLICT');
    }

    public function test_partner_cannot_see_other_tenant_subscription(): void
    {
        $foreignCustomer = Pelanggan::create([
            'mitra_id' => $this->partnerB->id,
            'kode_pelanggan' => 'KN-SUB-003',
            'nama_pelanggan' => 'Pelanggan B2',
            'no_hp' => '081200000012',
            'alamat_pasang' => 'Jalan B2',
            'status' => 'aktif',
        ]);
        $foreign = Langganan::create([
            'pelanggan_id' => $foreignCustomer->id,
            'paket_internet_id' => $this->package20->id,
            'tanggal_aktivasi' => '2026-11-01',
            'hari_aktivasi' => 1,
            'masa_tenggang_hari' => 7,
            'harga_snapshot' => 150000,
            'status' => 'aktif',
        ]);

        $this->actingAs($this->partnerUser)->getJson("/api/v1/subscriptions/{$foreign->id}")
            ->assertStatus(404);

        $this->actingAs($this->partnerUser)->getJson('/api/v1/subscriptions')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 0);
    }
}
