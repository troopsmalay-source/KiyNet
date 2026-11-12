<?php

namespace Tests\Feature\Admin;

use Database\Seeders\KiNetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MitraSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_mitra_customer_and_package_data_can_be_seeded_repeatedly(): void
    {
        $seeder = app(KiNetSeeder::class);
        $seeder->run();
        $seeder->run();

        $this->assertDatabaseCount('mitra', 4);
        $this->assertDatabaseCount('pelanggan', 4);
        $this->assertDatabaseCount('paket_internet', 4);
        $this->assertDatabaseCount('langganan', 4);
        $this->assertDatabaseCount('tagihan', 4);
        $this->assertDatabaseCount('tagihan_detail', 4);
        $this->assertDatabaseCount('pembayaran', 2);
        $this->assertDatabaseHas('mitra', [
            'kode_mitra' => 'MT-DEMO-004',
            'nama_mitra' => 'Mitra Binjai',
            'status' => 'nonaktif',
        ]);
        $this->assertDatabaseHas('pelanggan', [
            'kode_pelanggan' => 'KN-DEMO-001',
            'nama_pelanggan' => 'Andi Saputra',
        ]);
        $this->assertDatabaseHas('paket_internet', [
            'kode_paket' => 'PK-DEMO-004',
            'nama_paket' => 'KiNet Ultra 100',
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('langganan', [
            'status' => 'aktif',
            'pppoe_username' => 'andi.demo001',
        ]);
        $this->assertDatabaseHas('tagihan', [
            'no_invoice' => 'INV-DEMO-'.now()->format('Ym').'-001',
            'status' => 'lunas',
            'total_dibayar' => 150000,
        ]);
        $this->assertDatabaseHas('tagihan', [
            'no_invoice' => 'INV-REMINDER-'.now()->format('Ym').'-007',
            'status' => 'belum_bayar',
            'jatuh_tempo' => now()->addDays(7)->toDateString(),
        ]);
        $this->assertDatabaseHas('tagihan', [
            'no_invoice' => 'INV-DEMO-'.now()->format('Ym').'-002',
            'jatuh_tempo' => now()->addDays(3)->toDateString(),
        ]);
        $this->assertDatabaseHas('tagihan', [
            'no_invoice' => 'INV-DEMO-'.now()->format('Ym').'-004',
            'jatuh_tempo' => now()->addDay()->toDateString(),
        ]);
    }
}
