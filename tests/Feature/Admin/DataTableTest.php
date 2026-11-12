<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Mitra;
use App\Models\PaketInternet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DataTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_lists_search_and_paginate_all_records_server_side(): void
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
        $user->refresh();
        $mitraId = DB::table('mitra')->insertGetId([
            'kode_mitra' => 'MT-DT-001',
            'nama_mitra' => 'Mitra DataTable',
            'nama_pic' => 'PIC Demo',
            'no_hp' => '0811111111',
            'status' => 'aktif',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('mitra')->insert([
            'kode_mitra' => 'MT-DT-002',
            'nama_mitra' => 'Mitra Nonaktif',
            'nama_pic' => 'PIC Nonaktif',
            'no_hp' => '0822222222',
            'status' => 'nonaktif',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach ([
            ['kode_pelanggan' => 'DT-CUST-001', 'nama_pelanggan' => 'Pelanggan Alpha'],
            ['kode_pelanggan' => 'DT-CUST-002', 'nama_pelanggan' => 'Pelanggan Beta'],
        ] as $customer) {
            DB::table('pelanggan')->insert($customer + [
                'mitra_id' => $mitraId,
                'no_hp' => '08123456789',
                'alamat_pasang' => 'Alamat uji DataTable',
                'status' => 'aktif',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ([
            ['kode_paket' => 'DT-PKG-001', 'nama_paket' => 'Paket Fiber Alpha', 'harga' => 100000, 'is_active' => true],
            ['kode_paket' => 'DT-PKG-002', 'nama_paket' => 'Paket Fiber Beta', 'harga' => 200000, 'is_active' => false],
        ] as $package) {
            DB::table('paket_internet')->insert($package + [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->actingAs($user)
            ->get(route('admin.mitra.index'))
            ->assertOk()
            ->assertSee('Data Mitra')
            ->assertSee('class="mitra-page-card"', false)
            ->assertSee('aria-label="Breadcrumb"', false)
            ->assertSee('Beranda')
            ->assertSee('Pelanggan dikelola')
            ->assertSee('data-theme-toggle', false)
            ->assertSee('Aktifkan mode malam');
        $this->get(route('admin.paket.index'))
            ->assertOk()
            ->assertSee('Paket Internet')
            ->assertSee('class="paket-page-card"', false)
            ->assertSee('Paket nonaktif');
        $this->get(route('admin.pelanggan.index'))
            ->assertOk()
            ->assertSee('id="customerDetailModal"', false)
            ->assertSee('customer-detail-dialog')
            ->assertSee('Profil Pelanggan')
            ->assertSee('Koneksi Internet')
            ->assertSee('Riwayat Tagihan')
            ->assertSee('Riwayat Pembayaran')
            ->assertSee('Riwayat Aktivitas');
        $this->withHeader('X-Requested-With', 'XMLHttpRequest');

        $this->getJson(route('admin.pelanggan.index', $this->tableRequest('nama_pelanggan', 'Beta')))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 2)
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.kode_pelanggan', 'DT-CUST-002');

        $this->getJson(route('admin.mitra.index', $this->tableRequest('nama_mitra', 'DataTable')))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 2)
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.nama_mitra', 'Mitra DataTable');

        $this->getJson(route('admin.mitra.index', $this->tableRequest('nama_mitra', '') + ['status' => 'nonaktif']))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.nama_mitra', 'Mitra Nonaktif');

        $this->getJson(route('admin.paket.index', $this->tableRequest('nama_paket', 'Beta')))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 2)
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.kode_paket', 'DT-PKG-002');

        $this->getJson(route('admin.paket.index', $this->tableRequest('nama_paket', '', 1, 1)))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 2)
            ->assertJsonPath('recordsFiltered', 2)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.kode_paket', 'DT-PKG-002');

        $this->getJson(route('admin.paket.index', $this->tableRequest('nama_paket', '') + ['status' => '0']))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.kode_paket', 'DT-PKG-002')
            ->assertJsonPath('data.0.is_active', false);

        $this->post(route('admin.paket.store'), [
            'nama_paket' => 'KiNet Demo',
            'download_mbps' => 40,
            'upload_mbps' => 15,
            'harga' => 225000,
            'deskripsi' => 'Paket uji untuk alur admin.',
            'is_active' => '0',
        ])->assertRedirect(route('admin.paket.index'));

        $paket = PaketInternet::where('nama_paket', 'KiNet Demo')->firstOrFail();
        $this->get(route('admin.paket.edit', $paket))
            ->assertOk()
            ->assertSee($paket->kode_paket)
            ->assertSee('Simpan perubahan');

        $this->put(route('admin.paket.update', $paket), [
            'nama_paket' => 'KiNet Demo Plus',
            'download_mbps' => 50,
            'upload_mbps' => 20,
            'harga' => 275000,
            'deskripsi' => 'Paket uji diperbarui.',
            'is_active' => '1',
        ])->assertRedirect(route('admin.paket.index'));

        $this->assertDatabaseHas('paket_internet', [
            'id' => $paket->id,
            'nama_paket' => 'KiNet Demo Plus',
            'is_active' => true,
        ]);

        $this->post(route('admin.mitra.store'), [
            'nama_mitra' => 'Mitra Baru',
            'nama_pic' => 'PIC Baru',
            'no_hp' => '0833333333',
            'email' => 'mitra.baru@example.test',
            'alamat' => 'Alamat mitra baru',
            'status' => 'nonaktif',
        ])->assertRedirect(route('admin.mitra.index'));

        $mitra = Mitra::where('nama_mitra', 'Mitra Baru')->firstOrFail();
        $this->get(route('admin.mitra.edit', $mitra))
            ->assertOk()
            ->assertSee($mitra->kode_mitra)
            ->assertSee('Simpan perubahan');

        $this->put(route('admin.mitra.update', $mitra), [
            'nama_mitra' => 'Mitra Diperbarui',
            'nama_pic' => 'PIC Baru',
            'no_hp' => '0833333333',
            'email' => 'mitra.baru@example.test',
            'alamat' => 'Alamat mitra diperbarui',
            'status' => 'aktif',
        ])->assertRedirect(route('admin.mitra.index'));

        $this->assertDatabaseHas('mitra', [
            'id' => $mitra->id,
            'nama_mitra' => 'Mitra Diperbarui',
            'status' => 'aktif',
        ]);
    }

    private function tableRequest(string $column, string $search, int $start = 0, int $length = 10): array
    {
        return [
            'draw' => 1,
            'start' => $start,
            'length' => $length,
            'search' => ['value' => $search, 'regex' => 'false'],
            'order' => [['column' => 0, 'dir' => 'asc']],
            'columns' => [['data' => $column, 'name' => $column, 'searchable' => 'true', 'orderable' => 'true']],
        ];
    }
}
