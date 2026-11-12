<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_a_labeled_demo_preview_without_persisting_sample_records(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Ringkasan operasional')
            ->assertSee('Data demo')
            ->assertSee('Ringkasan pelanggan')
            ->assertSee('Ringkasan kemitraan')
            ->assertSee('Billing & keuangan')
            ->assertSee('Kesehatan jaringan & gateway MikroTik')
            ->assertSee('Aktivitas & insiden terbaru')
            ->assertSee('Total Pelanggan')
            ->assertSee('Isolir')
            ->assertSee('Router Binjai')
            ->assertSee('Data contoh');

        $this->assertDatabaseCount('pelanggan', 0);
        $this->assertDatabaseCount('tagihan', 0);
        $this->assertDatabaseCount('pembayaran', 0);
    }

    public function test_chart_returns_labeled_demo_series_for_the_requested_period(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (['7d' => 7, '30d' => 30, '3m' => 3, '12m' => 12] as $range => $periodCount) {
            $this->getJson(route('admin.dashboard.chart', ['range' => $range]))
                ->assertOk()
                ->assertJsonCount($periodCount, 'labels')
                ->assertJsonCount($periodCount, 'tagihan')
                ->assertJsonCount($periodCount, 'pembayaran')
                ->assertJsonCount($periodCount, 'pelangganBaru')
                ->assertJsonCount($periodCount, 'pelangganAktif')
                ->assertJsonCount($periodCount, 'pelangganTerisolir')
                ->assertJsonPath('demo', true);
        }
    }

    public function test_dashboard_and_charts_reflect_postgresql_records(): void
    {
        $now = now();
        $customerId = DB::table('pelanggan')->insertGetId([
            'kode_pelanggan' => 'TEST-DASH-001',
            'nama_pelanggan' => 'Pelanggan Dashboard',
            'no_hp' => '08123456789',
            'alamat_pasang' => 'Alamat pengujian',
            'status' => 'aktif',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $packageId = DB::table('paket_internet')->insertGetId([
            'kode_paket' => 'TEST-DASH',
            'nama_paket' => 'Paket pengujian dashboard',
            'harga' => 150000,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $subscriptionId = DB::table('langganan')->insertGetId([
            'pelanggan_id' => $customerId,
            'paket_internet_id' => $packageId,
            'tanggal_aktivasi' => $now->toDateString(),
            'hari_aktivasi' => $now->day,
            'harga_snapshot' => 150000,
            'status' => 'aktif',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $invoiceId = DB::table('tagihan')->insertGetId([
            'pelanggan_id' => $customerId,
            'langganan_id' => $subscriptionId,
            'no_invoice' => 'INV-TEST-DASH',
            'periode' => $now->format('Y-m'),
            'tanggal_tagihan' => $now->toDateString(),
            'jatuh_tempo' => $now->copy()->addDays(3)->toDateString(),
            'akhir_masa_tenggang' => $now->copy()->addDays(10)->toDateString(),
            'subtotal' => 150000,
            'total_tagihan' => 150000,
            'status' => 'belum_bayar',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('pembayaran')->insert([
            'tagihan_id' => $invoiceId,
            'metode' => 'transfer_manual',
            'nominal' => 50000,
            'dibayar_at' => $now,
            'status' => 'berhasil',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Data demo')
            ->assertSee('Rp 150.000')
            ->assertSee('Jatuh Tempo Hari Ini');

        $this->actingAs(User::factory()->create())
            ->getJson(route('admin.dashboard.chart', ['range' => '7d']))
            ->assertOk()
            ->assertJsonPath('tagihan.6', 150000)
            ->assertJsonPath('pembayaran.6', 50000)
            ->assertJsonPath('pelangganBaru.6', 1)
            ->assertJsonPath('pelangganAktif.6', 1)
            ->assertJsonPath('pelangganTerisolir.6', 0);
    }
}
