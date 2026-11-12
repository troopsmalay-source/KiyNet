<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CstInternalTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_renders_toolbar_filters_and_customer_table(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.cst-internal.index'))
            ->assertOk()
            ->assertSee('CST Internal')
            ->assertSee('Registrasi CST Baru')
            ->assertSee('Impor Data')
            ->assertSee('Ekspor CSV')
            ->assertSee('CST-202611-001')
            ->assertSee('Andi Saputra')
            ->assertSee('KiNet Home 20')
            ->assertSee('Router Medan Kota')
            ->assertSee('Data demo JSON')
            ->assertSee('id="cstStatusModal"', false)
            ->assertSee('id="cstCreateModal"', false);
    }

    public function test_search_and_filters_narrow_the_customer_list(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('admin.cst-internal.index', ['q' => 'siti.rahmawati']))
            ->assertOk()
            ->assertSee('Siti Rahmawati')
            ->assertDontSee('Andi Saputra');

        $this->get(route('admin.cst-internal.index', ['status' => 'Isolir']))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertDontSee('Andi Saputra');

        $this->get(route('admin.cst-internal.index', ['wilayah' => 'POP Binjai']))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('Fajar Nugroho')
            ->assertDontSee('Siti Rahmawati');

        $this->get(route('admin.cst-internal.index', ['q' => 'tidak-ada-hasil']))
            ->assertOk()
            ->assertSee('Tidak ada pelanggan yang cocok');
    }

    public function test_detail_page_shows_four_service_cards_and_history_tabs(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.cst-internal.show', 'CST-202611-001'))
            ->assertOk()
            ->assertSee('Profil Pelanggan')
            ->assertSee('Data Layanan')
            ->assertSee('Kontrol')
            ->assertSee('Riwayat Invoice')
            ->assertSee('Riwayat Pembayaran')
            ->assertSee('Perubahan Paket')
            ->assertSee('Isolasi')
            ->assertSee('Tiket Gangguan')
            ->assertSee('andi.saputra')
            ->assertSee('INV-202611-001')
            ->assertSee('TRX-QRIS-88231')
            ->assertSee('TKT-202610-014')
            ->assertSee('Lihat di peta');

        $this->get(route('admin.cst-internal.show', 'CST-TIDAK-ADA'))->assertNotFound();
    }

    public function test_status_change_requires_reason_and_appends_timeline_log(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('admin.cst-internal.status', 'CST-202611-001'), ['status' => 'Suspend'])
            ->assertSessionHasErrors('reason');

        $this->post(route('admin.cst-internal.status', 'CST-202611-001'), [
            'status' => 'Suspend',
            'reason' => 'Uji perubahan status oleh QA internal.',
        ])->assertRedirect(route('admin.cst-internal.show', 'CST-202611-001'));

        $this->get(route('admin.cst-internal.show', 'CST-202611-001'))
            ->assertOk()
            ->assertSee('Uji perubahan status oleh QA internal.')
            ->assertSee('Suspend');
    }

    public function test_new_customer_registration_generates_cst_number_and_profile(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('admin.cst-internal.store'), [
            'nama' => 'Pelanggan Baru Uji',
            'no_hp' => '081299990000',
            'email' => 'pelanggan.baru@example.test',
            'alamat' => 'Jl. Uji Registrasi No. 1, Medan',
            'wilayah' => 'POP Medan Kota',
            'paket' => 'KiNet Home 30',
            'router' => 'Router Medan Kota',
            'pppoe_username' => 'pelanggan.baru',
            'catatan' => 'Dibuat dari test registrasi CST.',
        ])->assertRedirect(route('admin.cst-internal.show', 'CST-202611-009'));

        $this->get(route('admin.cst-internal.show', 'CST-202611-009'))
            ->assertOk()
            ->assertSee('Pelanggan Baru Uji')
            ->assertSee('pelanggan.baru')
            ->assertSee('30 Mbps / 10 Mbps')
            ->assertSee('Registrasi CST baru.');
    }

    public function test_index_pagination_respects_per_page_and_page(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('admin.cst-internal.index', ['per_page' => 5]))
            ->assertOk()
            ->assertSee('Menampilkan')
            ->assertSee('1 – 5</strong>', false)
            ->assertSeeText('1 – 5');

        $this->get(route('admin.cst-internal.index', ['per_page' => 5, 'page' => 2]))
            ->assertOk()
            ->assertSee('6 – 8</strong>', false)
            ->assertSeeText('6 – 8');

        // per_page tidak valid jatuh ke default 10 (semua 8 pelanggan).
        $this->get(route('admin.cst-internal.index', ['per_page' => 99]))
            ->assertOk()
            ->assertSee('1 – 8</strong>', false)
            ->assertSeeText('1 – 8');

        // Filter terbawa saat pindah halaman.
        $this->get(route('admin.cst-internal.index', ['per_page' => 5, 'status' => 'Aktif', 'page' => 1]))
            ->assertOk()
            ->assertSee('status=Aktif', false);
    }

    public function test_index_sorting_orders_customers_and_toggles_direction(): void
    {
        $this->actingAs($this->admin());

        $response = $this->get(route('admin.cst-internal.index', ['sort' => 'nama', 'direction' => 'asc', 'per_page' => 50]));
        $response->assertOk();

        preg_match_all('/<strong>([^<]+)<\/strong>/', $response->getContent(), $matches);
        $names = array_values(array_filter($matches[1], function ($n) {
            return !str_starts_with($n, 'CST-')
                && !str_contains($n, '–')
                && !str_contains($n, 'Ekspor')
                && !str_contains($n, 'Pilih berkas')
                && !is_numeric($n);
        }));
        $sorted = $names;
        sort($sorted, SORT_STRING);
        $this->assertSame($sorted, $names);

        // Arah desc membalik urutan.
        preg_match_all('/<strong>([^<]+)<\/strong>/',
            $this->get(route('admin.cst-internal.index', ['sort' => 'nama', 'direction' => 'desc', 'per_page' => 50]))->getContent(),
            $descMatches
        );
        $descNames = array_values(array_filter($descMatches[1], function ($n) {
            return !str_starts_with($n, 'CST-')
                && !str_contains($n, '–')
                && !str_contains($n, 'Ekspor')
                && !str_contains($n, 'Pilih berkas')
                && !is_numeric($n);
        }));
        $this->assertSame(array_reverse($sorted), $descNames);

        // Kolom tidak sortable tidak boleh diurutkan (fallback id).
        $this->get(route('admin.cst-internal.index', ['sort' => 'alamat']))->assertOk();
    }

    public function test_export_streams_filtered_csv(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.cst-internal.export', ['status' => 'Aktif']))
            ->assertOk();

        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('CST-202611-001', $csv);
        $this->assertStringContainsString('Andi Saputra', $csv);
        $this->assertStringNotContainsString('Budi Santoso', $csv);
    }

    private function admin(): User
    {
        $roleId = DB::table('roles')->insertGetId([
            'name' => 'super_admin',
            'label' => 'Super Administrator',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::factory()->create(['name' => 'Admin Uji CST']);
        DB::table('users')->where('id', $user->id)->update(['role_id' => $roleId]);

        return $user->refresh();
    }
}
