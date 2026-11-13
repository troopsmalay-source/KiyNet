<?php

namespace Tests\Feature\Api;

use App\Models\InvoiceAccessToken;
use App\Models\Langganan;
use App\Models\Mitra;
use App\Models\PaketInternet;
use App\Models\Pelanggan;
use App\Models\Tagihan;
use App\Models\TaxCategory;
use App\Models\TaxSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2PublicTokenTaxTest extends TestCase
{
    use RefreshDatabase;

    private Mitra $partnerA;

    private Mitra $partnerB;

    private User $admin;

    private User $partnerUser;

    private User $otherPartnerUser;

    private Tagihan $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partnerA = Mitra::factory()->create();
        $this->partnerB = Mitra::factory()->create();
        $this->admin = User::factory()->withRole('admin')->create();
        $this->partnerUser = User::factory()->withRole('mitra')->forMitra($this->partnerA)->create();
        $this->otherPartnerUser = User::factory()->withRole('mitra')->forMitra($this->partnerB)->create();

        $package = PaketInternet::create([
            'kode_paket' => 'PK-PUB', 'nama_paket' => 'Public 20',
            'download_mbps' => 20, 'upload_mbps' => 5, 'harga' => 150000, 'is_active' => true,
        ]);
        $customer = Pelanggan::create([
            'mitra_id' => $this->partnerA->id,
            'kode_pelanggan' => 'KN-PUB-001',
            'nama_pelanggan' => 'Pelanggan Publik',
            'no_hp' => '081200000030',
            'alamat_pasang' => 'Jalan Rahasia 99',
            'nik' => '1271000000000001',
            'status' => 'aktif',
        ]);
        $subscription = Langganan::create([
            'pelanggan_id' => $customer->id,
            'paket_internet_id' => $package->id,
            'tanggal_aktivasi' => '2026-11-01',
            'hari_aktivasi' => 1,
            'masa_tenggang_hari' => 7,
            'harga_snapshot' => 150000,
            'status' => 'aktif',
        ]);

        $this->invoice = Tagihan::create([
            'pelanggan_id' => $customer->id,
            'langganan_id' => $subscription->id,
            'no_invoice' => 'INV-PUB-001',
            'periode' => '2026-11',
            'tanggal_tagihan' => '2026-11-01',
            'jatuh_tempo' => '2026-12-01',
            'akhir_masa_tenggang' => '2026-12-08',
            'subtotal' => 150000,
            'total_tagihan' => 150000,
            'status' => 'belum_bayar',
        ]);
    }

    public function test_public_token_returns_strict_allowlist_and_no_pii(): void
    {
        $token = $this->actingAs($this->partnerUser)
            ->postJson("/api/v1/invoices/{$this->invoice->id}/access-tokens")
            ->assertStatus(201)
            ->json('data.token');

        $response = $this->getJson("/api/v1/public/invoices/{$token}");

        $response->assertStatus(200)
            ->assertJsonPath('data.no_invoice', 'INV-PUB-001')
            ->assertJsonPath('data.total_idr', 150000)
            ->assertJsonPath('data.customer.nama_pelanggan', 'Pelanggan Publik')
            ->assertJsonMissingPath('data.customer.no_hp')
            ->assertJsonMissingPath('data.customer.alamat_pasang')
            ->assertJsonMissingPath('data.customer.nik');

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertSame('noindex, nofollow', $response->headers->get('X-Robots-Tag'));
    }

    public function test_revoked_and_expired_tokens_stop_working(): void
    {
        $token = $this->actingAs($this->partnerUser)
            ->postJson("/api/v1/invoices/{$this->invoice->id}/access-tokens")
            ->json('data.token');

        $access = InvoiceAccessToken::query()->firstOrFail();

        $this->actingAs($this->partnerUser)
            ->deleteJson("/api/v1/invoice-access-tokens/{$access->id}")
            ->assertStatus(200);

        $this->getJson("/api/v1/public/invoices/{$token}")->assertStatus(404);

        $second = $this->actingAs($this->partnerUser)
            ->postJson("/api/v1/invoices/{$this->invoice->id}/access-tokens")
            ->json('data.token');

        InvoiceAccessToken::query()->latest('id')->first()->update(['expires_at' => now()->subMinute()]);

        $this->getJson("/api/v1/public/invoices/{$second}")->assertStatus(404);
    }

    public function test_other_partner_cannot_issue_token_for_foreign_invoice(): void
    {
        $this->actingAs($this->otherPartnerUser)
            ->postJson("/api/v1/invoices/{$this->invoice->id}/access-tokens")
            ->assertStatus(404);
    }

    public function test_partner_tax_submission_flow_and_officer_review(): void
    {
        $ppn = TaxCategory::query()->where('code', 'PPN')->firstOrFail();

        $created = $this->actingAs($this->partnerUser)->postJson('/api/v1/tax/submissions', [
            'tax_category_id' => $ppn->id,
            'period' => '2026-11',
            'gross_amount_idr' => 10000000,
            'tax_amount_idr' => 1100000,
            'items' => [
                ['description' => 'Penjualan jasa internet', 'amount_idr' => 10000000],
            ],
        ]);

        $created->assertStatus(201)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.partner_id', $this->partnerA->id);

        $id = $created->json('data.id');

        $this->actingAs($this->otherPartnerUser)
            ->getJson("/api/v1/tax/submissions/{$id}")
            ->assertStatus(404);

        $this->actingAs($this->partnerUser)
            ->postJson("/api/v1/tax/submissions/{$id}/submit")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'submitted');

        // Partners cannot review their own submission.
        $this->actingAs($this->partnerUser)
            ->postJson("/api/v1/tax/submissions/{$id}/review", ['decision' => 'approved'])
            ->assertStatus(403);

        $this->actingAs($this->admin)
            ->postJson("/api/v1/tax/submissions/{$id}/review", [
                'decision' => 'approved',
                'notes' => 'Sesuai bukti potong',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');

        $this->actingAs($this->admin)
            ->getJson("/api/v1/reports/partners/{$this->partnerA->id}/tax")
            ->assertStatus(200)
            ->assertJsonPath('data.categories.0.category', 'PPN')
            ->assertJsonPath('data.categories.0.tax_amount_idr', 1100000);

        $this->assertDatabaseHas('activity_logs', ['aksi' => 'tax.submission.review']);
    }

    public function test_tax_submission_is_unique_per_partner_category_period(): void
    {
        $ppn = TaxCategory::query()->where('code', 'PPN')->firstOrFail();
        $payload = [
            'tax_category_id' => $ppn->id,
            'period' => '2026-11',
            'gross_amount_idr' => 1000000,
            'tax_amount_idr' => 110000,
        ];

        $this->actingAs($this->partnerUser)->postJson('/api/v1/tax/submissions', $payload)->assertStatus(201);
        $this->actingAs($this->partnerUser)->postJson('/api/v1/tax/submissions', $payload)->assertStatus(409);

        $this->assertSame(1, TaxSubmission::query()->count());
    }
}
