<?php

namespace Tests\Feature\Api;

use App\Models\Langganan;
use App\Models\Mitra;
use App\Models\PaketInternet;
use App\Models\PartnerBandwidthContract;
use App\Models\Pelanggan;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2PartnerAuditReportTest extends TestCase
{
    use RefreshDatabase;

    private Mitra $partnerA;

    private Mitra $partnerB;

    private User $admin;

    private User $partnerUser;

    private User $otherPartnerUser;

    private PaketInternet $package;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partnerA = Mitra::factory()->create();
        $this->partnerB = Mitra::factory()->create();
        $this->admin = User::factory()->withRole('admin')->create();
        $this->partnerUser = User::factory()->withRole('mitra')->forMitra($this->partnerA)->create();
        $this->otherPartnerUser = User::factory()->withRole('mitra')->forMitra($this->partnerB)->create();
        $this->package = PaketInternet::create([
            'kode_paket' => 'PK-RPT', 'nama_paket' => 'Report 20',
            'download_mbps' => 20, 'upload_mbps' => 5, 'harga' => 150000, 'is_active' => true,
        ]);
    }

    public function test_partner_staff_management_is_tenant_bound(): void
    {
        $created = $this->actingAs($this->partnerUser)->postJson("/api/v1/partners/{$this->partnerA->id}/users", [
            'name' => 'Staff A',
            'email' => 'staff.a@example.test',
            'password' => 'Secret123!',
        ]);

        $created->assertStatus(201)->assertJsonPath('data.role', 'mitra');

        $this->actingAs($this->partnerUser)->postJson("/api/v1/partners/{$this->partnerB->id}/users", [
            'name' => 'Staff B',
            'email' => 'staff.b@example.test',
            'password' => 'Secret123!',
        ])->assertStatus(404);

        $this->actingAs($this->admin)->postJson("/api/v1/partners/{$this->partnerB->id}/users", [
            'name' => 'Staff B',
            'email' => 'staff.b2@example.test',
            'password' => 'Secret123!',
        ])->assertStatus(201);

        $this->actingAs($this->partnerUser)->getJson("/api/v1/partners/{$this->partnerA->id}/users")
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_bandwidth_contract_is_central_managed_and_partner_can_request_capacity(): void
    {
        $contract = $this->actingAs($this->admin)->postJson("/api/v1/partners/{$this->partnerA->id}/bandwidth-contracts", [
            'committed_mbps' => 100,
            'price_idr_per_mbps' => 50000,
            'starts_at' => '2026-11-01',
            'status' => 'active',
        ]);

        $contract->assertStatus(201)->assertJsonPath('data.committed_mbps', 100);
        $contractId = $contract->json('data.id');

        $this->actingAs($this->partnerUser)->postJson("/api/v1/partners/{$this->partnerA->id}/bandwidth-contracts", [
            'committed_mbps' => 500,
            'price_idr_per_mbps' => 1,
            'starts_at' => '2026-12-01',
            'status' => 'active',
        ])->assertStatus(403);

        $this->actingAs($this->partnerUser)
            ->postJson("/api/v1/partners/{$this->partnerA->id}/bandwidth-contracts/{$contractId}/capacity-change-requests", [
                'requested_mbps' => 200,
                'reason' => 'Permintaan pelanggan baru',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'pending');

        $this->actingAs($this->otherPartnerUser)
            ->postJson("/api/v1/partners/{$this->partnerA->id}/bandwidth-contracts/{$contractId}/capacity-change-requests", [
                'requested_mbps' => 300,
                'reason' => 'Tidak berhak',
            ])->assertStatus(404);

        $this->assertSame(1, PartnerBandwidthContract::query()->count());
    }

    public function test_billing_report_is_tenant_scoped_and_supports_central_filter(): void
    {
        $this->invoiceFor($this->partnerA, 'INV-RPT-A', 150000, 50000);
        $this->invoiceFor($this->partnerB, 'INV-RPT-B', 200000, 200000);

        $this->actingAs($this->partnerUser)->getJson('/api/v1/reports/billing')
            ->assertStatus(200)
            ->assertJsonPath('data.invoices_count', 1)
            ->assertJsonPath('data.total_billed_idr', 150000)
            ->assertJsonPath('data.total_paid_idr', 50000)
            ->assertJsonPath('data.outstanding_idr', 100000);

        $this->actingAs($this->admin)->getJson('/api/v1/reports/billing')
            ->assertStatus(200)
            ->assertJsonPath('data.invoices_count', 2)
            ->assertJsonPath('data.total_billed_idr', 350000);

        $this->actingAs($this->admin)->getJson("/api/v1/reports/billing?partner_id={$this->partnerB->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.invoices_count', 1)
            ->assertJsonPath('data.total_paid_idr', 200000);
    }

    public function test_privileged_actions_are_audited_and_audit_is_central_only(): void
    {
        $customer = $this->customerFor($this->partnerA, 'KN-AUD-001');
        $subscription = $this->subscriptionFor($customer);

        $this->actingAs($this->admin)
            ->withHeader('Idempotency-Key', 'audit-inv-1')
            ->postJson('/api/v1/invoices', [
                'subscription_id' => $subscription->id,
                'period' => '2026-11',
            ])->assertStatus(201);

        $this->actingAs($this->admin)->getJson('/api/v1/audit-logs?module=billing')
            ->assertStatus(200)
            ->assertJsonPath('data.0.action', 'invoice.generate');

        $this->actingAs($this->partnerUser)->getJson('/api/v1/audit-logs')
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_whatsapp_test_endpoint_is_central_only(): void
    {
        $this->actingAs($this->admin)->postJson('/api/v1/integrations/whatsapp/test', [
            'recipient' => '081200000099',
        ])->assertStatus(200)->assertJsonPath('data.status', 'sent');

        $this->actingAs($this->partnerUser)->postJson('/api/v1/integrations/whatsapp/test', [
            'recipient' => '081200000099',
        ])->assertStatus(403);
    }

    private function customerFor(Mitra $mitra, string $code): Pelanggan
    {
        return Pelanggan::create([
            'mitra_id' => $mitra->id,
            'kode_pelanggan' => $code,
            'nama_pelanggan' => 'Pelanggan '.$code,
            'no_hp' => '081200000040',
            'alamat_pasang' => 'Jalan Report',
            'status' => 'aktif',
        ]);
    }

    private function subscriptionFor(Pelanggan $customer): Langganan
    {
        return Langganan::create([
            'pelanggan_id' => $customer->id,
            'paket_internet_id' => $this->package->id,
            'tanggal_aktivasi' => '2026-11-01',
            'hari_aktivasi' => 1,
            'masa_tenggang_hari' => 7,
            'harga_snapshot' => 150000,
            'status' => 'aktif',
        ]);
    }

    private function invoiceFor(Mitra $mitra, string $number, int $total, int $paid): Tagihan
    {
        $customer = $this->customerFor($mitra, 'KN-'.$number);
        $subscription = $this->subscriptionFor($customer);

        return Tagihan::create([
            'pelanggan_id' => $customer->id,
            'langganan_id' => $subscription->id,
            'no_invoice' => $number,
            'periode' => '2026-11',
            'tanggal_tagihan' => '2026-11-01',
            'jatuh_tempo' => '2026-12-01',
            'akhir_masa_tenggang' => '2026-12-08',
            'subtotal' => $total,
            'total_tagihan' => $total,
            'total_dibayar' => $paid,
            'status' => $paid >= $total ? 'lunas' : ($paid > 0 ? 'sebagian' : 'belum_bayar'),
        ]);
    }
}
