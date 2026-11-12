<?php

namespace Tests\Feature\Admin;

use App\Models\Langganan;
use App\Models\Mitra;
use App\Models\PaketInternet;
use App\Models\Pelanggan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LanggananTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_lists_searches_and_filters_subscriptions(): void
    {
        $user = $this->createAdmin();
        $customer = $this->createCustomer('SUB-CUST-001', 'Pelanggan Langganan');
        $otherCustomer = $this->createCustomer('SUB-CUST-002', 'Pelanggan Menunggu');
        $package = $this->createPackage('SUB-PKG-001', 'Paket Fiber Demo');
        $this->createSubscription($customer, $package, 'aktif', 'user.langganan');
        $this->createSubscription($otherCustomer, $package, 'menunggu', null);

        $this->actingAs($user)
            ->get(route('admin.langganan.index'))
            ->assertOk()
            ->assertSee('class="langganan-page-card"', false)
            ->assertSee('aria-label="Breadcrumb"', false)
            ->assertSee('Total langganan')
            ->assertSee('Status langganan')
            ->assertSee('>Langganan</span>', false)
            ->assertSee('href="'.route('admin.langganan.index').'"', false);

        $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('admin.langganan.index', $this->tableRequest('Pelanggan Langganan')))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 2)
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.kode_pelanggan', 'SUB-CUST-001')
            ->assertJsonPath('data.0.status', 'aktif');

        $this->getJson(route('admin.langganan.index', $this->tableRequest('') + ['status' => 'menunggu']))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.kode_pelanggan', 'SUB-CUST-002');
    }

    public function test_admin_creates_and_updates_subscription_with_price_snapshot(): void
    {
        $user = $this->createAdmin();
        $customer = $this->createCustomer('SUB-CUST-003', 'Pelanggan CRUD');
        $package = $this->createPackage('SUB-PKG-002', 'Paket Awal', 150000);
        $newPackage = $this->createPackage('SUB-PKG-003', 'Paket Baru', 250000);
        $activationDate = now()->toDateString();

        $this->actingAs($user)
            ->get(route('admin.langganan.create'))
            ->assertOk()
            ->assertSee('Pilih pelanggan')
            ->assertSee('Simpan langganan');

        $this->post(route('admin.langganan.store'), [
            'pelanggan_id' => $customer->id,
            'paket_internet_id' => $package->id,
            'tanggal_aktivasi' => $activationDate,
            'tanggal_jatuh_tempo' => now()->addMonth()->toDateString(),
            'tanggal_berakhir' => '',
            'masa_tenggang_hari' => 7,
            'status' => 'menunggu',
            'pppoe_username' => 'crud.subscription',
            'ip_static' => '',
            'profile_router' => 'home-demo',
        ])->assertRedirect(route('admin.langganan.index'));

        $subscription = Langganan::where('pelanggan_id', $customer->id)->firstOrFail();
        $this->assertSame('150000.00', $subscription->harga_snapshot);
        $this->assertSame((int) now()->format('j'), $subscription->hari_aktivasi);

        $this->get(route('admin.langganan.edit', $subscription))
            ->assertOk()
            ->assertSee('Paket Awal')
            ->assertSee('Simpan perubahan');

        $package->update(['harga' => 175000]);
        $this->put(route('admin.langganan.update', $subscription), [
            'pelanggan_id' => $customer->id,
            'paket_internet_id' => $package->id,
            'tanggal_aktivasi' => $activationDate,
            'tanggal_jatuh_tempo' => now()->addMonth()->toDateString(),
            'tanggal_berakhir' => '',
            'masa_tenggang_hari' => 10,
            'status' => 'aktif',
            'pppoe_username' => 'crud.subscription',
            'ip_static' => '',
            'profile_router' => 'home-demo',
        ])->assertRedirect(route('admin.langganan.index'));
        $this->assertSame('150000.00', $subscription->fresh()->harga_snapshot);

        $this->put(route('admin.langganan.update', $subscription), [
            'pelanggan_id' => $customer->id,
            'paket_internet_id' => $newPackage->id,
            'tanggal_aktivasi' => $activationDate,
            'tanggal_jatuh_tempo' => now()->addMonth()->toDateString(),
            'tanggal_berakhir' => '',
            'masa_tenggang_hari' => 10,
            'status' => 'aktif',
            'pppoe_username' => 'crud.subscription',
            'ip_static' => '',
            'profile_router' => 'home-demo',
        ])->assertRedirect(route('admin.langganan.index'));
        $this->assertSame('250000.00', $subscription->fresh()->harga_snapshot);
    }

    public function test_mitra_only_sees_and_edits_subscriptions_for_its_customers(): void
    {
        $now = now();
        $roleId = DB::table('roles')->insertGetId([
            'name' => 'mitra',
            'label' => 'Mitra',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $mitra = Mitra::create([
            'kode_mitra' => 'SUB-MITRA-001',
            'nama_mitra' => 'Mitra Subscription',
            'status' => 'aktif',
        ]);
        $user = User::factory()->create();
        DB::table('users')->where('id', $user->id)->update([
            'role_id' => $roleId,
            'mitra_id' => $mitra->id,
        ]);
        $user->refresh();
        $ownCustomer = $this->createCustomer('SUB-MITRA-CUST-001', 'Pelanggan Mitra');
        $ownCustomer->update(['mitra_id' => $mitra->id]);
        $otherCustomer = $this->createCustomer('SUB-MITRA-CUST-002', 'Pelanggan Lain');
        $package = $this->createPackage('SUB-MITRA-PKG-001', 'Paket Mitra');
        $ownSubscription = $this->createSubscription($ownCustomer, $package, 'aktif', 'mitra.own');
        $otherSubscription = $this->createSubscription($otherCustomer, $package, 'aktif', 'mitra.other');

        $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('admin.langganan.index', $this->tableRequest('')))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.kode_pelanggan', 'SUB-MITRA-CUST-001');

        $this->get(route('admin.langganan.edit', $ownSubscription))->assertOk();
        $this->get(route('admin.langganan.edit', $otherSubscription))->assertNotFound();
    }

    private function createAdmin(): User
    {
        $now = now();
        $roleId = DB::table('roles')->insertGetId([
            'name' => 'super_admin',
            'label' => 'Super Administrator',
            'created_at' => $now,
            'updated_at' => $now,
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
            'alamat_pasang' => 'Alamat uji langganan',
            'status' => 'aktif',
        ]);
    }

    private function createPackage(string $code, string $name, int $price = 150000): PaketInternet
    {
        return PaketInternet::create([
            'kode_paket' => $code,
            'nama_paket' => $name,
            'download_mbps' => 20,
            'upload_mbps' => 5,
            'harga' => $price,
            'is_active' => true,
        ]);
    }

    private function createSubscription(Pelanggan $customer, PaketInternet $package, string $status, ?string $username): Langganan
    {
        return Langganan::create([
            'pelanggan_id' => $customer->id,
            'paket_internet_id' => $package->id,
            'tanggal_aktivasi' => now()->toDateString(),
            'hari_aktivasi' => (int) now()->format('j'),
            'tanggal_jatuh_tempo' => now()->addMonth()->toDateString(),
            'masa_tenggang_hari' => 7,
            'harga_snapshot' => $package->harga,
            'status' => $status,
            'pppoe_username' => $username,
        ]);
    }

    private function tableRequest(string $search): array
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
