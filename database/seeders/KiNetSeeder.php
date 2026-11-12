<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\User;
use App\Models\Mitra;
use App\Models\Pelanggan;
use App\Models\PaketInternet;
use App\Models\Langganan;
use App\Models\Tagihan;
use App\Models\TagihanDetail;
use App\Models\Pembayaran;
class KiNetSeeder extends Seeder {
    public function run(): void {
        foreach ([
            ['name'=>'super_admin','label'=>'Super Administrator'],
            ['name'=>'admin','label'=>'Administrator'],
            ['name'=>'mitra','label'=>'Mitra'],
        ] as $role) Role::firstOrCreate(['name'=>$role['name']], ['label'=>$role['label']]);
        $role = Role::where('name','super_admin')->firstOrFail();
        User::firstOrCreate(['email'=>'admin@kinet.local'], [
            'name'=>'Super Admin KiNet','password'=>Hash::make('ChangeMe123!'),
            'role_id'=>$role->id,'is_active'=>true,
        ]);

        $mitraSamples = [
            [
                'kode_mitra' => 'MT-DEMO-001',
                'nama_mitra' => 'Mitra Medan Kota',
                'nama_pic' => 'Rizky Pratama',
                'no_hp' => '081234560101',
                'email' => 'medan.kota@example.test',
                'alamat' => 'Jl. Sisingamangaraja, Medan',
                'status' => 'aktif',
            ],
            [
                'kode_mitra' => 'MT-DEMO-002',
                'nama_mitra' => 'Mitra Medan Utara',
                'nama_pic' => 'Nadia Putri',
                'no_hp' => '081234560102',
                'email' => 'medan.utara@example.test',
                'alamat' => 'Jl. Marelan Raya, Medan',
                'status' => 'aktif',
            ],
            [
                'kode_mitra' => 'MT-DEMO-003',
                'nama_mitra' => 'Mitra Deli Serdang',
                'nama_pic' => 'Fajar Ramadhan',
                'no_hp' => '081234560103',
                'email' => 'deli.serdang@example.test',
                'alamat' => 'Jl. Medan-Batang Kuis, Deli Serdang',
                'status' => 'aktif',
            ],
            [
                'kode_mitra' => 'MT-DEMO-004',
                'nama_mitra' => 'Mitra Binjai',
                'nama_pic' => 'Siti Amelia',
                'no_hp' => '081234560104',
                'email' => 'binjai@example.test',
                'alamat' => 'Jl. Jenderal Sudirman, Binjai',
                'status' => 'nonaktif',
            ],
        ];

        $mitras = [];
        foreach ($mitraSamples as $sample) {
            $mitra = Mitra::withTrashed()->firstOrCreate(
                ['kode_mitra' => $sample['kode_mitra']],
                $sample,
            );
            if ($mitra->trashed()) {
                $mitra->restore();
            }
            $mitras[$sample['kode_mitra']] = $mitra;
        }

        foreach ([
            ['kode_pelanggan' => 'KN-DEMO-001', 'nama_pelanggan' => 'Andi Saputra', 'no_hp' => '081234567801', 'alamat_pasang' => 'Jl. Sisingamangaraja, Medan', 'status' => 'aktif', 'mitra' => 'MT-DEMO-001'],
            ['kode_pelanggan' => 'KN-DEMO-002', 'nama_pelanggan' => 'Dewi Lestari', 'no_hp' => '081234567802', 'alamat_pasang' => 'Jl. Brigjen Katamso, Medan', 'status' => 'aktif', 'mitra' => 'MT-DEMO-001'],
            ['kode_pelanggan' => 'KN-DEMO-003', 'nama_pelanggan' => 'Budi Santoso', 'no_hp' => '081234567803', 'alamat_pasang' => 'Jl. Marelan Raya, Medan', 'status' => 'calon', 'mitra' => 'MT-DEMO-002'],
            ['kode_pelanggan' => 'KN-DEMO-004', 'nama_pelanggan' => 'Maya Putri', 'no_hp' => '081234567804', 'alamat_pasang' => 'Jl. Medan-Batang Kuis, Deli Serdang', 'status' => 'isolir', 'mitra' => 'MT-DEMO-003'],
        ] as $sample) {
            $mitra = $mitras[$sample['mitra']];
            unset($sample['mitra']);
            $pelanggan = Pelanggan::withTrashed()->firstOrCreate(
                ['kode_pelanggan' => $sample['kode_pelanggan']],
                $sample + ['mitra_id' => $mitra->id],
            );
            if ($pelanggan->trashed()) {
                $pelanggan->restore();
            }
        }

        $packages = [];
        foreach ([
            ['kode_paket' => 'PK-DEMO-001', 'nama_paket' => 'KiNet Home 20', 'download_mbps' => 20, 'upload_mbps' => 5, 'harga' => 150000, 'deskripsi' => 'Paket internet harian untuk kebutuhan rumah.', 'is_active' => true],
            ['kode_paket' => 'PK-DEMO-002', 'nama_paket' => 'KiNet Family 30', 'download_mbps' => 30, 'upload_mbps' => 10, 'harga' => 200000, 'deskripsi' => 'Koneksi stabil untuk keluarga dan belajar daring.', 'is_active' => true],
            ['kode_paket' => 'PK-DEMO-003', 'nama_paket' => 'KiNet Gamer 50', 'download_mbps' => 50, 'upload_mbps' => 20, 'harga' => 275000, 'deskripsi' => 'Kecepatan lebih tinggi untuk streaming dan bermain game.', 'is_active' => true],
            ['kode_paket' => 'PK-DEMO-004', 'nama_paket' => 'KiNet Ultra 100', 'download_mbps' => 100, 'upload_mbps' => 50, 'harga' => 450000, 'deskripsi' => 'Paket performa tinggi untuk banyak perangkat.', 'is_active' => false],
        ] as $sample) {
            $packages[$sample['kode_paket']] = PaketInternet::firstOrCreate(
                ['kode_paket' => $sample['kode_paket']],
                $sample,
            );
        }

        foreach ([
            ['kode_pelanggan' => 'KN-DEMO-001', 'kode_paket' => 'PK-DEMO-001', 'tanggal_aktivasi' => '2026-10-12', 'tanggal_jatuh_tempo' => '2026-11-12', 'status' => 'aktif', 'pppoe_username' => 'andi.demo001'],
            ['kode_pelanggan' => 'KN-DEMO-002', 'kode_paket' => 'PK-DEMO-002', 'tanggal_aktivasi' => '2026-10-15', 'tanggal_jatuh_tempo' => '2026-11-15', 'status' => 'aktif', 'pppoe_username' => 'dewi.demo002'],
            ['kode_pelanggan' => 'KN-DEMO-003', 'kode_paket' => 'PK-DEMO-003', 'tanggal_aktivasi' => '2026-11-01', 'tanggal_jatuh_tempo' => '2026-12-01', 'status' => 'menunggu', 'pppoe_username' => null],
            ['kode_pelanggan' => 'KN-DEMO-004', 'kode_paket' => 'PK-DEMO-001', 'tanggal_aktivasi' => '2026-09-20', 'tanggal_jatuh_tempo' => '2026-10-20', 'status' => 'ditangguhkan', 'pppoe_username' => 'maya.demo004'],
        ] as $sample) {
            $customer = Pelanggan::where('kode_pelanggan', $sample['kode_pelanggan'])->firstOrFail();
            $package = $packages[$sample['kode_paket']];
            $activationDate = $sample['tanggal_aktivasi'];
            Langganan::firstOrCreate(
                ['pelanggan_id' => $customer->id],
                [
                    'paket_internet_id' => $package->id,
                    'tanggal_aktivasi' => $activationDate,
                    'hari_aktivasi' => (int) date('j', strtotime($activationDate)),
                    'tanggal_jatuh_tempo' => $sample['tanggal_jatuh_tempo'],
                    'masa_tenggang_hari' => 7,
                    'harga_snapshot' => $package->harga,
                    'status' => $sample['status'],
                    'pppoe_username' => $sample['pppoe_username'],
                ],
            );
        }

        $admin = User::where('email', 'admin@kinet.local')->firstOrFail();
        $period = now()->format('Y-m');
        $invoiceDate = now()->startOfMonth()->toDateString();
        foreach ([
            [
                'kode_pelanggan' => 'KN-DEMO-001',
                'no_invoice' => 'INV-DEMO-'.now()->format('Ym').'-001',
                'periode' => $period,
                'total' => 150000,
                'paid' => 150000,
                'status' => 'lunas',
                'reference' => 'TRX-DEMO-INV-001',
                'paid_days_ago' => 3,
                'due_days_away' => 7,
            ],
            [
                'kode_pelanggan' => 'KN-DEMO-002',
                'no_invoice' => 'INV-DEMO-'.now()->format('Ym').'-002',
                'periode' => $period,
                'total' => 200000,
                'paid' => 50000,
                'status' => 'sebagian',
                'reference' => 'TRX-DEMO-INV-002',
                'paid_days_ago' => 1,
                'due_days_away' => 3,
            ],
            [
                'kode_pelanggan' => 'KN-DEMO-004',
                'no_invoice' => 'INV-DEMO-'.now()->format('Ym').'-004',
                'periode' => $period,
                'total' => 150000,
                'paid' => 0,
                'status' => 'belum_bayar',
                'reference' => null,
                'paid_days_ago' => null,
                'due_days_away' => 1,
            ],
        ] as $sample) {
            $customer = Pelanggan::where('kode_pelanggan', $sample['kode_pelanggan'])->firstOrFail();
            $subscription = Langganan::where('pelanggan_id', $customer->id)->firstOrFail();
            $paidAt = $sample['paid_days_ago'] !== null
                ? now()->subDays($sample['paid_days_ago'])->setTime(10, 30)
                : null;
            $dueDate = now()->addDays($sample['due_days_away'])->toDateString();
            $graceDate = now()->addDays($sample['due_days_away'] + $subscription->masa_tenggang_hari)->toDateString();
            $invoice = Tagihan::firstOrCreate(
                ['langganan_id' => $subscription->id, 'periode' => $sample['periode']],
                [
                    'pelanggan_id' => $customer->id,
                    'no_invoice' => $sample['no_invoice'],
                    'tanggal_tagihan' => $invoiceDate,
                    'jatuh_tempo' => $dueDate,
                    'akhir_masa_tenggang' => $graceDate,
                    'subtotal' => $sample['total'],
                    'diskon' => 0,
                    'denda' => 0,
                    'total_tagihan' => $sample['total'],
                    'total_dibayar' => $sample['paid'],
                    'status' => $sample['status'],
                    'paid_at' => $sample['status'] === 'lunas' ? $paidAt : null,
                ],
            );
            if ($invoice->no_invoice === $sample['no_invoice']) {
                $invoice->update([
                    'tanggal_tagihan' => $invoiceDate,
                    'jatuh_tempo' => $dueDate,
                    'akhir_masa_tenggang' => $graceDate,
                    'paid_at' => $sample['status'] === 'lunas' ? $paidAt : null,
                ]);
            }
            if ($invoice->no_invoice === $sample['no_invoice']) {
                TagihanDetail::firstOrCreate(
                    ['tagihan_id' => $invoice->id],
                    [
                        'deskripsi' => $subscription->paketInternet->nama_paket.' - '.$sample['periode'],
                        'qty' => 1,
                        'harga_satuan' => $sample['total'],
                        'subtotal' => $sample['total'],
                    ],
                );
            }

            if ($sample['paid'] > 0 && $invoice->no_invoice === $sample['no_invoice']) {
                $payment = Pembayaran::firstOrCreate(
                    ['nomor_referensi' => $sample['reference']],
                    [
                        'tagihan_id' => $invoice->id,
                        'metode' => 'transfer_manual',
                        'nominal' => $sample['paid'],
                        'dibayar_at' => $paidAt,
                        'verified_by' => $admin->id,
                        'status' => 'berhasil',
                    ],
                );
                $payment->update(['dibayar_at' => $paidAt]);
            }
        }

        $reminderCustomer = Pelanggan::where('kode_pelanggan', 'KN-DEMO-001')->firstOrFail();
        $reminderSubscription = Langganan::where('pelanggan_id', $reminderCustomer->id)->firstOrFail();
        $reminderPeriod = now()->subMonth()->format('Y-m');
        $reminderInvoiceNumber = 'INV-REMINDER-'.now()->format('Ym').'-007';
        $reminderInvoice = Tagihan::firstOrCreate(
            ['langganan_id' => $reminderSubscription->id, 'periode' => $reminderPeriod],
            [
                'pelanggan_id' => $reminderCustomer->id,
                'no_invoice' => $reminderInvoiceNumber,
                'tanggal_tagihan' => now()->subMonth()->startOfMonth()->toDateString(),
                'jatuh_tempo' => now()->addDays(7)->toDateString(),
                'akhir_masa_tenggang' => now()->addDays(14)->toDateString(),
                'subtotal' => $reminderSubscription->harga_snapshot,
                'diskon' => 0,
                'denda' => 0,
                'total_tagihan' => $reminderSubscription->harga_snapshot,
                'total_dibayar' => 0,
                'status' => 'belum_bayar',
            ],
        );
        if ($reminderInvoice->no_invoice === $reminderInvoiceNumber) {
            $reminderInvoice->update([
                'jatuh_tempo' => now()->addDays(7)->toDateString(),
                'akhir_masa_tenggang' => now()->addDays(14)->toDateString(),
            ]);
            TagihanDetail::firstOrCreate(
                ['tagihan_id' => $reminderInvoice->id],
                [
                    'deskripsi' => $reminderSubscription->paketInternet->nama_paket.' - '.$reminderPeriod,
                    'qty' => 1,
                    'harga_satuan' => $reminderSubscription->harga_snapshot,
                    'subtotal' => $reminderSubscription->harga_snapshot,
                ],
            );
        }
    }
}
