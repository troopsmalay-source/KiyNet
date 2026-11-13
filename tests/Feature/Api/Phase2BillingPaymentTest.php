<?php

namespace Tests\Feature\Api;

use App\Models\Langganan;
use App\Models\Mitra;
use App\Models\PaketInternet;
use App\Models\Pelanggan;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2BillingPaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $partnerUser;

    private Mitra $partnerA;

    private Langganan $subscription;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partnerA = Mitra::factory()->create();
        $this->admin = User::factory()->withRole('admin')->create();
        $this->partnerUser = User::factory()->withRole('mitra')->forMitra($this->partnerA)->create();

        $package = PaketInternet::create([
            'kode_paket' => 'PK-B1', 'nama_paket' => 'Billing 20',
            'download_mbps' => 20, 'upload_mbps' => 5, 'harga' => 150000, 'is_active' => true,
        ]);
        $customer = Pelanggan::create([
            'mitra_id' => $this->partnerA->id,
            'kode_pelanggan' => 'KN-BIL-001',
            'nama_pelanggan' => 'Pelanggan Billing',
            'no_hp' => '081200000020',
            'alamat_pasang' => 'Jalan Billing',
            'status' => 'aktif',
        ]);
        $this->subscription = Langganan::create([
            'pelanggan_id' => $customer->id,
            'paket_internet_id' => $package->id,
            'tanggal_aktivasi' => '2026-11-01',
            'hari_aktivasi' => 1,
            'tanggal_jatuh_tempo' => '2026-12-01',
            'masa_tenggang_hari' => 7,
            'harga_snapshot' => 150000,
            'status' => 'aktif',
        ]);
    }

    public function test_invoice_generation_is_idempotent_and_snapshot_is_immutable(): void
    {
        $payload = ['subscription_id' => $this->subscription->id, 'period' => '2026-11'];

        $first = $this->actingAs($this->admin)
            ->withHeader('Idempotency-Key', 'inv-key-1')
            ->postJson('/api/v1/invoices', $payload);

        $first->assertStatus(201)
            ->assertJsonPath('data.total_idr', 150000)
            ->assertJsonPath('data.status', 'belum_bayar');

        $second = $this->actingAs($this->admin)
            ->withHeader('Idempotency-Key', 'inv-key-1')
            ->postJson('/api/v1/invoices', $payload);

        $second->assertStatus(201);
        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertDatabaseCount('tagihan', 1);
        $this->assertDatabaseCount('tagihan_detail', 1);
        $this->assertDatabaseHas('notification_outbox', ['template' => 'invoice_created', 'status' => 'pending']);

        // A different key for the same subscription+period is a conflict.
        $this->actingAs($this->admin)
            ->withHeader('Idempotency-Key', 'inv-key-2')
            ->postJson('/api/v1/invoices', $payload)
            ->assertStatus(409);
    }

    public function test_invoice_generation_requires_idempotency_key(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/invoices', [
                'subscription_id' => $this->subscription->id,
                'period' => '2026-11',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_partner_cannot_read_another_tenant_invoice(): void
    {
        $invoice = $this->makeInvoice();

        $otherPartner = Mitra::factory()->create();
        $otherUser = User::factory()->withRole('mitra')->forMitra($otherPartner)->create();

        $this->actingAs($otherUser)->getJson("/api/v1/invoices/{$invoice->id}")->assertStatus(404);
        $this->actingAs($this->partnerUser)->getJson("/api/v1/invoices/{$invoice->id}")->assertStatus(200);
    }

    public function test_void_requires_reason_and_rejects_paid_invoice(): void
    {
        $invoice = $this->makeInvoice();

        $this->actingAs($this->admin)->postJson("/api/v1/invoices/{$invoice->id}/void", [])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');

        $this->actingAs($this->admin)->postJson("/api/v1/invoices/{$invoice->id}/void", [
            'reason' => 'Salah periode',
        ])->assertStatus(200)->assertJsonPath('data.status', 'dibatalkan');

        $paid = $this->makeInvoice('2026-12');
        $paid->update(['status' => 'lunas', 'total_dibayar' => 150000]);

        $this->actingAs($this->admin)->postJson("/api/v1/invoices/{$paid->id}/void", [
            'reason' => 'Tidak boleh',
        ])->assertStatus(409);
    }

    public function test_payment_intent_is_idempotent_and_validates_amount(): void
    {
        $invoice = $this->makeInvoice();

        $first = $this->actingAs($this->partnerUser)
            ->withHeader('Idempotency-Key', 'pay-key-1')
            ->postJson("/api/v1/invoices/{$invoice->id}/payment-intents", ['method' => 'qris']);

        $first->assertStatus(201)
            ->assertJsonPath('data.amount_idr', 150000)
            ->assertJsonPath('data.status', 'pending');

        $second = $this->actingAs($this->partnerUser)
            ->withHeader('Idempotency-Key', 'pay-key-1')
            ->postJson("/api/v1/invoices/{$invoice->id}/payment-intents", ['method' => 'qris']);

        $second->assertStatus(200);
        $this->assertSame($first->json('data.id'), $second->json('data.id'));

        $this->actingAs($this->partnerUser)
            ->withHeader('Idempotency-Key', 'pay-key-2')
            ->postJson("/api/v1/invoices/{$invoice->id}/payment-intents", [
                'method' => 'qris',
                'amount_idr' => 200000,
            ])->assertStatus(422);
    }

    public function test_signed_webhook_verifies_replay_and_amount(): void
    {
        config(['services.payment.webhook_secret' => 'test-secret']);

        $invoice = $this->makeInvoice();
        $intent = $this->actingAs($this->partnerUser)
            ->withHeader('Idempotency-Key', 'pay-key-webhook')
            ->postJson("/api/v1/invoices/{$invoice->id}/payment-intents", ['method' => 'qris'])
            ->json('data');

        $body = json_encode([
            'event_id' => 'evt-001',
            'type' => 'payment.succeeded',
            'reference' => $intent['reference'],
            'amount_idr' => 150000,
            'provider_transaction_id' => 'trx-001',
        ]);

        $signature = hash_hmac('sha256', $body, 'test-secret');

        $this->call('POST', '/api/v1/webhooks/payments/fake', [], [], [], [
            'HTTP_X_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], $body)->assertStatus(200)->assertJsonPath('data.status', 'processed');

        $invoice->refresh();
        $this->assertSame('lunas', $invoice->status);
        $this->assertSame(150000, (int) $invoice->total_dibayar);

        // Replay of the same provider event must not double-credit.
        $this->call('POST', '/api/v1/webhooks/payments/fake', [], [], [], [
            'HTTP_X_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], $body)->assertStatus(200)->assertJsonPath('data.status', 'already_processed');

        $this->assertSame(150000, (int) $invoice->fresh()->total_dibayar);
    }

    public function test_webhook_rejects_bad_signature_and_amount_mismatch(): void
    {
        config(['services.payment.webhook_secret' => 'test-secret']);

        $invoice = $this->makeInvoice();
        $intent = $this->actingAs($this->partnerUser)
            ->withHeader('Idempotency-Key', 'pay-key-bad')
            ->postJson("/api/v1/invoices/{$invoice->id}/payment-intents", ['method' => 'qris'])
            ->json('data');

        $body = json_encode([
            'event_id' => 'evt-bad',
            'reference' => $intent['reference'],
            'amount_idr' => 999,
        ]);

        $this->call('POST', '/api/v1/webhooks/payments/fake', [], [], [], [
            'HTTP_X_SIGNATURE' => 'wrong-signature',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], $body)->assertStatus(401);

        $signature = hash_hmac('sha256', $body, 'test-secret');

        $this->call('POST', '/api/v1/webhooks/payments/fake', [], [], [], [
            'HTTP_X_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], $body)->assertStatus(422);

        $this->assertDatabaseHas('pembayaran', ['id' => $intent['id'], 'status' => 'pending']);
    }

    public function test_refund_and_manual_reconcile_are_audited(): void
    {
        $invoice = $this->makeInvoice();
        $intent = $this->actingAs($this->admin)
            ->withHeader('Idempotency-Key', 'pay-key-reconcile')
            ->postJson("/api/v1/invoices/{$invoice->id}/payment-intents", ['method' => 'transfer_manual'])
            ->json('data');

        $this->actingAs($this->admin)->postJson("/api/v1/payments/{$intent['id']}/reconcile", [
            'note' => 'Transfer verified in bank statement',
        ])->assertStatus(200)->assertJsonPath('data.status', 'berhasil');

        $this->actingAs($this->admin)->postJson("/api/v1/payments/{$intent['id']}/refunds", [
            'amount_idr' => 50000,
            'reason' => 'Kelebihan bayar',
        ])->assertStatus(201)->assertJsonPath('data.amount_idr', 50000);

        $this->actingAs($this->admin)->postJson("/api/v1/payments/{$intent['id']}/refunds", [
            'amount_idr' => 200000,
            'reason' => 'Terlalu besar',
        ])->assertStatus(409);

        $this->assertDatabaseHas('activity_logs', ['aksi' => 'payment.reconcile']);
        $this->assertDatabaseHas('activity_logs', ['aksi' => 'payment.refund.request']);
    }

    private function makeInvoice(string $period = '2026-11'): Tagihan
    {
        $response = $this->actingAs($this->admin)
            ->withHeader('Idempotency-Key', 'inv-'.$period)
            ->postJson('/api/v1/invoices', [
                'subscription_id' => $this->subscription->id,
                'period' => $period,
            ]);

        $response->assertStatus(201);

        return Tagihan::findOrFail($response->json('data.id'));
    }
}
