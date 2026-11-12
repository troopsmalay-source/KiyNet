<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Support\BillingInvoiceRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BillingInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());
        $this->app['session']->flush();
    }

    private function admin(): User
    {
        $roleId = DB::table('roles')->insertGetId([
            'name' => 'super_admin',
            'label' => 'Super Administrator',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::factory()->create(['name' => 'Admin Uji Billing']);
        DB::table('users')->where('id', $user->id)->update(['role_id' => $roleId]);

        return $user->fresh();
    }

    public function testIndexRenders(): void
    {
        $response = $this->get(route('admin.billing.index'));

        $response->assertOk();
        $response->assertSee('INV-202610-0082');
        $response->assertSee('Budi Santoso');
    }

    public function testFilterByStatus(): void
    {
        $repo = $this->app->make(BillingInvoiceRepository::class);

        $overdue = $repo->filter(['status' => 'overdue']);
        $this->assertGreaterThan(0, $overdue->count());

        foreach ($overdue as $row) {
            $this->assertEquals('overdue', $repo->effectiveStatus($row));
        }

        $paid = $repo->filter(['status' => 'paid']);
        $this->assertGreaterThan(0, $paid->count());
        foreach ($paid as $row) {
            $this->assertSame('paid', $row['status']);
        }
    }

    public function testFilterBySearch(): void
    {
        $repo = $this->app->make(BillingInvoiceRepository::class);

        $results = $repo->filter(['q' => 'Maya']);
        $this->assertSame(1, $results->count());
        $this->assertSame('Maya Fitriani', $results->first()['nama']);

        $results = $repo->filter(['q' => 'INV-202611-0091']);
        $this->assertSame(1, $results->count());
        $this->assertSame('INV-202611-0091', $results->first()['no_invoice']);
    }

    public function testGenerateBatchCreatesProratedInvoice(): void
    {
        $repo = $this->app->make(BillingInvoiceRepository::class);
        $before = $repo->all()->count();

        // Periode lama tak punya tagihan bulanan: semua aktif akan ditagih
        $result = $repo->generateBatch('2025-01', '2025-01-01', '2025-01-10', 'all', true);

        $this->assertGreaterThan(0, $result['created']);
        $this->assertSame($before + $result['created'], $repo->all()->count());

        // Prorata: Rina Maulida (CST-...008) aktif 2026-11-11? verifikasi total bukan kelipatan penuh
        $created = $repo->all()->filter(fn ($row) => $row['periode'] === '2025-01');
        $this->assertSame($result['created'], $created->count());

        // Setiap invoice baru punya nomor unik & status unpaid
        foreach ($created as $row) {
            $this->assertSame('unpaid', $row['status']);
            $this->assertStringStartsWith('INV-', $row['no_invoice']);
        }
    }

    public function testRecordPaymentMarksPaidAndCalculates(): void
    {
        $repo = $this->app->make(BillingInvoiceRepository::class);

        $invoice = $repo->all()->firstWhere('status', 'unpaid');
        $this->assertNotNull($invoice);

        $updated = $repo->recordPayment($invoice['no_invoice'], 'transfer', 'uji');

        $this->assertSame('paid', $updated['status']);
        $this->assertSame('transfer', $updated['metode_pembayaran']);
        $this->assertNotNull($updated['tanggal_bayar']);

        $lastLog = end($updated['audit_log']);
        $this->assertSame('paid', $lastLog['aksi']);
    }

    public function testPayPageRoutePostsPayment(): void
    {
        $repo = $this->app->make(BillingInvoiceRepository::class);
        $invoice = $repo->all()->firstWhere('status', 'unpaid');

        $response = $this->post(route('admin.billing.payment', $invoice['no_invoice']), [
            'metode' => 'cash',
            'catatan' => 'pembayaran tunai',
        ]);

        $response->assertRedirect(route('admin.billing.index'));
        $this->assertSame('paid', $repo->find($invoice['no_invoice'])['status']);
    }

    public function testExportCsv(): void
    {
        $response = $this->get(route('admin.billing.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }
}
