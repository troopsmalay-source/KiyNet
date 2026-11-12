<?php

namespace Tests\Feature\Admin;

use App\Models\Langganan;
use App\Models\Mitra;
use App\Models\PaketInternet;
use App\Models\Pembayaran;
use App\Models\Pelanggan;
use App\Models\Tagihan;
use App\Models\TagihanDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TagihanTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_invoice_and_records_partial_then_full_payment(): void
    {
        $user = $this->createAdmin();
        $subscription = $this->createSubscription($this->createCustomer('BILL-CUST-001', 'Pelanggan Tagihan'));

        $this->actingAs($user)
            ->get(route('admin.tagihan.index'))
            ->assertOk()
            ->assertSee('Buat tagihan')
            ->assertSee('aria-label="Breadcrumb"', false);

        $this->actingAs($user)->get(route('admin.tagihan.create'))
            ->assertOk()
            ->assertSee('Terbitkan invoice')
            ->assertSee('Pelanggan Tagihan');

        $period = now()->format('Y-m');
        $dueDate = now()->addDays(7)->toDateString();
        $this->post(route('admin.tagihan.store'), [
            'langganan_id' => $subscription->id,
            'periode' => $period,
            'jatuh_tempo' => $dueDate,
            'diskon' => 10000,
        ])->assertSessionHasNoErrors();

        $invoice = Tagihan::where('langganan_id', $subscription->id)->firstOrFail();
        $this->assertSame('belum_bayar', $invoice->status);
        $this->assertSame('140000.00', $invoice->total_tagihan);
        $this->assertSame(1, TagihanDetail::where('tagihan_id', $invoice->id)->count());

        $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('admin.tagihan.index', $this->tableRequest('Pelanggan Tagihan')))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.no_invoice', $invoice->no_invoice)
            ->assertJsonPath('data.0.status', 'belum_bayar');

        $this->post(route('admin.tagihan.store'), [
            'langganan_id' => $subscription->id,
            'periode' => $period,
            'jatuh_tempo' => $dueDate,
            'diskon' => 0,
        ])->assertSessionHasErrors('periode');

        $this->post(route('admin.tagihan.pembayaran.store', $invoice), [
            'nominal' => 40000,
            'nomor_referensi' => 'TEST-PAYMENT-001',
            'dibayar_at' => now()->format('Y-m-d H:i:s'),
        ])->assertRedirect(route('admin.tagihan.show', $invoice));
        $this->assertSame('sebagian', $invoice->fresh()->status);
        $this->assertSame('40000.00', $invoice->fresh()->total_dibayar);
        $this->assertSame(1, Pembayaran::where('tagihan_id', $invoice->id)->count());

        $this->from(route('admin.tagihan.show', $invoice))
            ->post(route('admin.tagihan.pembayaran.store', $invoice), [
                'nominal' => 110000,
                'dibayar_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertSessionHasErrors('nominal');

        $this->post(route('admin.tagihan.pembayaran.store', $invoice), [
            'nominal' => 100000,
            'nomor_referensi' => 'TEST-PAYMENT-002',
            'dibayar_at' => now()->format('Y-m-d H:i:s'),
        ])->assertRedirect(route('admin.tagihan.show', $invoice));
        $this->assertSame('lunas', $invoice->fresh()->status);
        $this->assertSame('140000.00', $invoice->fresh()->total_dibayar);
        $this->get(route('admin.tagihan.show', $invoice))
            ->assertOk()
            ->assertSee('Riwayat pembayaran')
            ->assertSee('Sisa tagihan');
    }

    public function test_mitra_only_lists_and_views_its_own_customer_invoices(): void
    {
        $roleId = DB::table('roles')->insertGetId([
            'name' => 'mitra',
            'label' => 'Mitra',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $mitra = Mitra::create([
            'kode_mitra' => 'BILL-MITRA-001',
            'nama_mitra' => 'Mitra Tagihan',
            'status' => 'aktif',
        ]);
        $user = User::factory()->create();
        DB::table('users')->where('id', $user->id)->update([
            'role_id' => $roleId,
            'mitra_id' => $mitra->id,
        ]);
        $user->refresh();
        $ownCustomer = $this->createCustomer('BILL-CUST-002', 'Pelanggan Mitra');
        $ownCustomer->update(['mitra_id' => $mitra->id]);
        $ownInvoice = $this->createInvoice($this->createSubscription($ownCustomer));
        $otherInvoice = $this->createInvoice($this->createSubscription($this->createCustomer('BILL-CUST-003', 'Pelanggan Lain')));

        $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('admin.tagihan.index', $this->tableRequest()))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.no_invoice', $ownInvoice->no_invoice);

        $this->get(route('admin.tagihan.show', $ownInvoice))->assertOk();
        $this->get(route('admin.tagihan.show', $otherInvoice))->assertNotFound();
    }

    private function createAdmin(): User
    {
        $roleId = DB::table('roles')->insertGetId([
            'name' => 'super_admin',
            'label' => 'Super Administrator',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = User::factory()->create();
        DB::table('users')->where('id', $user->id)->update(['role_id' => $roleId]);

        return $user->fresh();
    }

    private function createCustomer(string $code, string $name): Pelanggan
    {
        return Pelanggan::create([
            'kode_pelanggan' => $code,
            'nama_pelanggan' => $name,
            'no_hp' => '08123456789',
            'alamat_pasang' => 'Alamat uji tagihan',
            'status' => 'aktif',
        ]);
    }

    private function createSubscription(Pelanggan $customer): Langganan
    {
        $package = PaketInternet::create([
            'kode_paket' => 'BILL-PKG-'.str_pad((string) $customer->id, 3, '0', STR_PAD_LEFT),
            'nama_paket' => 'Paket Tagihan Demo',
            'download_mbps' => 20,
            'upload_mbps' => 5,
            'harga' => 150000,
            'is_active' => true,
        ]);

        return Langganan::create([
            'pelanggan_id' => $customer->id,
            'paket_internet_id' => $package->id,
            'tanggal_aktivasi' => now()->toDateString(),
            'hari_aktivasi' => (int) now()->format('j'),
            'tanggal_jatuh_tempo' => now()->addMonth()->toDateString(),
            'masa_tenggang_hari' => 7,
            'harga_snapshot' => 150000,
            'status' => 'aktif',
        ]);
    }

    private function createInvoice(Langganan $subscription): Tagihan
    {
        return Tagihan::create([
            'pelanggan_id' => $subscription->pelanggan_id,
            'langganan_id' => $subscription->id,
            'no_invoice' => 'BILL-INV-'.str_pad((string) $subscription->id, 4, '0', STR_PAD_LEFT),
            'periode' => now()->format('Y-m'),
            'tanggal_tagihan' => now()->toDateString(),
            'jatuh_tempo' => now()->addDays(7)->toDateString(),
            'akhir_masa_tenggang' => now()->addDays(14)->toDateString(),
            'subtotal' => 150000,
            'diskon' => 0,
            'denda' => 0,
            'total_tagihan' => 150000,
            'total_dibayar' => 0,
            'status' => 'belum_bayar',
        ]);
    }

    private function tableRequest(string $search = ''): array
    {
        return [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => $search, 'regex' => 'false'],
            'order' => [],
            'columns' => [],
        ];
    }
}
