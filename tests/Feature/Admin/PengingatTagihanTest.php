<?php

namespace Tests\Feature\Admin;

use App\Models\Langganan;
use App\Models\Mitra;
use App\Models\PaketInternet;
use App\Models\Pelanggan;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PengingatTagihanTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_four_tabs_and_only_unpaid_invoices_in_due_date_tabs(): void
    {
        $user = $this->createUserWithRole('super_admin');
        $customer = $this->createCustomer('REM-CUST-001', 'Pelanggan Pengingat');
        $sevenDays = $this->createInvoice($customer, 'belum_bayar', 7);
        $this->createInvoice($customer, 'sebagian', 3);
        $this->createInvoice($customer, 'belum_bayar', 1);
        $this->createInvoice($customer, 'lunas', 7);

        $this->actingAs($user)
            ->get(route('admin.pengingat.index'))
            ->assertOk()
            ->assertSee('Semua pelanggan')
            ->assertSee('7 hari lagi jatuh tempo')
            ->assertSee('3 hari lagi jatuh tempo')
            ->assertSee('1 hari lagi jatuh tempo')
            ->assertSee('aria-label="Breadcrumb"', false);

        $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('admin.pengingat.index', $this->tableRequest()))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 4);

        $this->getJson(route('admin.pengingat.index', $this->tableRequest() + ['days' => 7]))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.no_invoice', $sevenDays->no_invoice)
            ->assertJsonPath('data.0.status', 'belum_bayar');

        $this->getJson(route('admin.pengingat.index', $this->tableRequest() + ['days' => 3]))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.status', 'sebagian');

        $this->getJson(route('admin.pengingat.index', $this->tableRequest() + ['days' => 1]))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.status', 'belum_bayar');
    }

    public function test_mitra_only_sees_reminders_for_its_customers(): void
    {
        $user = $this->createUserWithRole('mitra');
        $mitra = Mitra::create([
            'kode_mitra' => 'REM-MITRA-001',
            'nama_mitra' => 'Mitra Pengingat',
            'status' => 'aktif',
        ]);
        DB::table('users')->where('id', $user->id)->update(['mitra_id' => $mitra->id]);
        $user->refresh();
        $ownCustomer = $this->createCustomer('REM-CUST-002', 'Pelanggan Mitra');
        $ownCustomer->update(['mitra_id' => $mitra->id]);
        $ownInvoice = $this->createInvoice($ownCustomer, 'belum_bayar', 3);
        $otherInvoice = $this->createInvoice($this->createCustomer('REM-CUST-003', 'Pelanggan Lain'), 'belum_bayar', 3);

        $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('admin.pengingat.index', $this->tableRequest() + ['days' => 3]))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.no_invoice', $ownInvoice->no_invoice)
            ->assertJsonMissing(['no_invoice' => $otherInvoice->no_invoice]);
    }

    private function createUserWithRole(string $role): User
    {
        $roleId = DB::table('roles')->insertGetId([
            'name' => $role,
            'label' => ucfirst($role),
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
            'alamat_pasang' => 'Alamat uji pengingat',
            'status' => 'aktif',
        ]);
    }

    private function createInvoice(Pelanggan $customer, string $status, int $daysUntilDue): Tagihan
    {
        $suffix = str_pad((string) (Pelanggan::count().Langganan::count()+1), 3, '0', STR_PAD_LEFT);
        $package = PaketInternet::create([
            'kode_paket' => 'REM-PKG-'.$suffix,
            'nama_paket' => 'Paket Pengingat',
            'download_mbps' => 20,
            'upload_mbps' => 5,
            'harga' => 150000,
            'is_active' => true,
        ]);
        $subscription = Langganan::create([
            'pelanggan_id' => $customer->id,
            'paket_internet_id' => $package->id,
            'tanggal_aktivasi' => now()->toDateString(),
            'hari_aktivasi' => (int) now()->format('j'),
            'tanggal_jatuh_tempo' => now()->addDays($daysUntilDue)->toDateString(),
            'masa_tenggang_hari' => 7,
            'harga_snapshot' => 150000,
            'status' => 'aktif',
        ]);

        return Tagihan::create([
            'pelanggan_id' => $customer->id,
            'langganan_id' => $subscription->id,
            'no_invoice' => 'REM-INV-'.$suffix,
            'periode' => now()->format('Y-m'),
            'tanggal_tagihan' => now()->toDateString(),
            'jatuh_tempo' => now()->addDays($daysUntilDue)->toDateString(),
            'akhir_masa_tenggang' => now()->addDays($daysUntilDue + 7)->toDateString(),
            'subtotal' => 150000,
            'diskon' => 0,
            'denda' => 0,
            'total_tagihan' => 150000,
            'total_dibayar' => $status === 'sebagian' ? 50000 : ($status === 'lunas' ? 150000 : 0),
            'status' => $status,
        ]);
    }

    private function tableRequest(): array
    {
        return [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => '', 'regex' => 'false'],
            'order' => [],
            'columns' => [],
        ];
    }
}
