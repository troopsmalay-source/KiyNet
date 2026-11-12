<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Support\PackageServiceRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PackageServiceTest extends TestCase
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

        $user = User::factory()->create(['name' => 'Admin Uji Paket']);
        DB::table('users')->where('id', $user->id)->update(['role_id' => $roleId]);

        return $user->fresh();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nama' => 'KiNet Home Ultra 50',
            'kategori' => 'retail',
            'siklus_tagihan' => 'bulanan',
            'is_active' => '1',
            'is_custom' => '0',
            'custom_pelanggan' => [],
            'download_mbps' => '50',
            'upload_mbps' => '20',
            'mikrotik_profile' => 'home-50m',
            'rate_limit' => '20M/50M',
            'ip_pool' => '100.64.5.0/24',
            'queue_priority' => '4',
            'harga' => '500000',
            'biaya_instalasi' => '150000',
            'biaya_addon' => '0',
            'diskon_jenis' => null,
            'diskon_value' => '0',
            'diskon_berlaku_sampai' => null,
            'fup_enabled' => '0',
            'fup_limit_gb' => null,
            'fup_throttle_rx' => null,
            'fup_throttle_tx' => null,
            'fup_aksi' => null,
        ], $overrides);
    }

    public function testIndexRenders(): void
    {
        $response = $this->get(route('admin.packages.index'));

        $response->assertOk();
        $response->assertSee('Paket & Layanan');
        $response->assertSee('KiNet Home 10');
        $response->assertSee('KiNet Dedicated SOHO 100');
    }

    public function testStoreCreatesPackage(): void
    {
        $response = $this->post(route('admin.packages.store'), $this->payload());

        $response->assertRedirect(route('admin.packages.index'));
        $this->assertNotNull(
            $this->app->make(PackageServiceRepository::class)->all()
                ->firstWhere('nama', 'KiNet Home Ultra 50')
        );
    }

    public function testToggleStatusFlipsActive(): void
    {
        $this->post(route('admin.packages.store'), $this->payload());

        $repo = $this->app->make(PackageServiceRepository::class);
        $record = $repo->all()->firstWhere('nama', 'KiNet Home Ultra 50');

        $this->assertTrue((bool) $record['is_active']);

        $this->post(route('admin.packages.toggle', $record['id']));
        $record = $repo->all()->firstWhere('nama', 'KiNet Home Ultra 50');
        $this->assertFalse((bool) $record['is_active']);

        $this->post(route('admin.packages.toggle', $record['id']));
        $record = $repo->all()->firstWhere('nama', 'KiNet Home Ultra 50');
        $this->assertTrue((bool) $record['is_active']);
    }

    public function testDestroySoftDeletes(): void
    {
        $repo = $this->app->make(PackageServiceRepository::class);
        $count = $repo->all()->count();

        $response = $this->delete(route('admin.packages.destroy', 'PKG-001'));

        $response->assertRedirect(route('admin.packages.index'));
        $this->assertSame($count - 1, $repo->all()->count());
        $this->assertNull($repo->find('PKG-001'));
    }

    public function testValidasiFieldWajib(): void
    {
        $response = $this->post(route('admin.packages.store'), $this->payload([
            'nama' => '',
            'download_mbps' => '0',
            'queue_priority' => '9',
            'kategori' => 'bogus',
        ]));

        $response->assertSessionHasErrors(['nama', 'download_mbps', 'queue_priority', 'kategori']);
    }

    public function testFilterBySearch(): void
    {
        $repo = $this->app->make(PackageServiceRepository::class);

        $results = $repo->filter(['q' => 'Dedicated']);
        $this->assertSame(1, $results->count());
        $this->assertSame('KiNet Dedicated SOHO 100', $results->first()['nama']);

        $results = $repo->filter(['status' => 'inactive']);
        $this->assertSame(1, $results->count());
        $this->assertSame('KiNet Mitra Reseller 30', $results->first()['nama']);

        $results = $repo->filter(['kecepatan' => '31-100']);
        $this->assertSame(3, $results->count());
    }
}
