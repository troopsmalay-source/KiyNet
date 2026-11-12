<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('label', 100);
            $table->timestamps();
        });

        Schema::create('mitra', function (Blueprint $table) {
            $table->id();
            $table->string('kode_mitra', 30)->unique();
            $table->string('nama_mitra', 150);
            $table->string('nama_pic', 150)->nullable();
            $table->string('no_hp', 25)->nullable();
            $table->string('email')->nullable();
            $table->text('alamat')->nullable();
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('id')->constrained('roles')->nullOnDelete();
            $table->foreignId('mitra_id')->nullable()->after('role_id')->constrained('mitra')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('password');
        });

        Schema::create('routers', function (Blueprint $table) {
            $table->id();
            $table->string('nama_router', 100);
            $table->string('lokasi')->nullable();
            $table->string('ip_address', 45);
            $table->unsignedSmallInteger('api_port')->default(8728);
            $table->string('username');
            $table->text('password_encrypted');
            $table->string('identity')->nullable();
            $table->enum('jenis', ['pppoe', 'hotspot', 'lainnya'])->default('pppoe');
            $table->enum('status', ['aktif', 'nonaktif', 'gangguan'])->default('aktif');
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamps();
        });

        Schema::create('paket_internet', function (Blueprint $table) {
            $table->id();
            $table->string('kode_paket', 30)->unique();
            $table->string('nama_paket', 100);
            $table->unsignedInteger('download_mbps')->nullable();
            $table->unsignedInteger('upload_mbps')->nullable();
            $table->decimal('harga', 15, 2);
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pelanggan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mitra_id')->nullable()->constrained('mitra')->nullOnDelete();
            $table->string('kode_pelanggan', 30)->unique();
            $table->string('nama_pelanggan', 150);
            $table->string('no_hp', 25);
            $table->string('email')->nullable();
            $table->text('alamat_pasang');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('nik', 30)->nullable();
            $table->enum('status', ['calon', 'aktif', 'isolir', 'nonaktif'])->default('calon');
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['mitra_id', 'status']);
        });

        Schema::create('langganan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pelanggan_id')->constrained('pelanggan')->restrictOnDelete();
            $table->foreignId('paket_internet_id')->constrained('paket_internet')->restrictOnDelete();
            $table->date('tanggal_aktivasi');
            $table->unsignedTinyInteger('hari_aktivasi')->comment('Tanggal dasar aktivasi 1-31');
            $table->date('tanggal_jatuh_tempo')->nullable();
            $table->date('tanggal_berakhir')->nullable();
            $table->unsignedTinyInteger('masa_tenggang_hari')->default(7);
            $table->decimal('harga_snapshot', 15, 2);
            $table->enum('status', ['menunggu', 'aktif', 'ditangguhkan', 'berhenti'])->default('menunggu');
            $table->string('pppoe_username')->nullable()->unique();
            $table->text('pppoe_password_encrypted')->nullable();
            $table->string('ip_static', 45)->nullable();
            $table->string('profile_router')->nullable();
            $table->timestamps();
            $table->index(['status', 'tanggal_jatuh_tempo']);
        });

        Schema::create('pelanggan_router', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pelanggan_id')->constrained('pelanggan')->cascadeOnDelete();
            $table->foreignId('router_id')->constrained('routers')->restrictOnDelete();
            $table->string('service_name')->nullable();
            $table->string('secret_name')->nullable();
            $table->string('remote_address', 45)->nullable();
            $table->enum('status_sinkronisasi', ['pending', 'synced', 'failed'])->default('pending');
            $table->text('pesan_error')->nullable();
            $table->timestamps();
            $table->unique(['pelanggan_id', 'router_id']);
        });

        Schema::create('tagihan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pelanggan_id')->constrained('pelanggan')->restrictOnDelete();
            $table->foreignId('langganan_id')->constrained('langganan')->restrictOnDelete();
            $table->string('no_invoice', 50)->unique();
            $table->string('periode', 7);
            $table->date('tanggal_tagihan');
            $table->date('jatuh_tempo');
            $table->date('akhir_masa_tenggang');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('diskon', 15, 2)->default(0);
            $table->decimal('denda', 15, 2)->default(0);
            $table->decimal('total_tagihan', 15, 2);
            $table->decimal('total_dibayar', 15, 2)->default(0);
            $table->enum('status', ['draft', 'belum_bayar', 'sebagian', 'lunas', 'dibatalkan'])->default('draft');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['langganan_id', 'periode']);
            $table->index(['status', 'jatuh_tempo']);
        });

        Schema::create('tagihan_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('tagihan')->cascadeOnDelete();
            $table->string('deskripsi', 200);
            $table->unsignedInteger('qty')->default(1);
            $table->decimal('harga_satuan', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });

        Schema::create('payment_gateway', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50);
            $table->string('nama_kanal', 100);
            $table->text('credential_encrypted')->nullable();
            $table->boolean('is_active')->default(false);
            $table->json('konfigurasi')->nullable();
            $table->timestamps();
        });

        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('tagihan')->restrictOnDelete();
            $table->foreignId('payment_gateway_id')->nullable()->constrained('payment_gateway')->nullOnDelete();
            $table->enum('metode', ['qris', 'virtual_account', 'transfer_manual']);
            $table->decimal('nominal', 15, 2);
            $table->string('nomor_referensi', 120)->nullable()->unique();
            $table->string('provider_transaction_id', 120)->nullable()->unique();
            $table->string('bukti_transfer')->nullable();
            $table->timestamp('kadaluarsa_at')->nullable();
            $table->timestamp('dibayar_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending', 'berhasil', 'ditolak', 'kadaluarsa', 'refund'])->default('pending');
            $table->json('provider_payload')->nullable();
            $table->timestamps();
            $table->index(['tagihan_id', 'status']);
        });

        Schema::create('payment_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50);
            $table->string('provider_event_id', 150)->unique();
            $table->string('event_type', 100)->nullable();
            $table->json('payload');
            $table->enum('status_proses', ['received', 'processed', 'failed'])->default('received');
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('invoice', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->unique()->constrained('tagihan')->restrictOnDelete();
            $table->string('nomor_invoice', 50)->unique();
            $table->string('file_pdf')->nullable();
            $table->timestamp('tanggal_terbit')->nullable();
            $table->json('snapshot_data')->nullable();
            $table->enum('status', ['draft', 'terbit', 'batal'])->default('draft');
            $table->timestamps();
        });

        Schema::create('pengingat_tagihan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('tagihan')->cascadeOnDelete();
            $table->enum('kanal', ['whatsapp', 'sms', 'email']);
            $table->string('tujuan', 150);
            $table->string('jenis_pengingat', 50);
            $table->enum('status', ['queued', 'sent', 'failed'])->default('queued');
            $table->text('response_provider')->nullable();
            $table->timestamp('dikirim_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mikrotik_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->nullable()->constrained('routers')->nullOnDelete();
            $table->foreignId('pelanggan_id')->nullable()->constrained('pelanggan')->nullOnDelete();
            $table->string('aksi', 80);
            $table->enum('status', ['success', 'failed', 'queued'])->default('queued');
            $table->json('request_data')->nullable();
            $table->text('response_data')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('aksi', 100);
            $table->string('modul', 100);
            $table->text('deskripsi')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('mikrotik_logs');
        Schema::dropIfExists('pengingat_tagihan');
        Schema::dropIfExists('invoice');
        Schema::dropIfExists('payment_webhook_logs');
        Schema::dropIfExists('pembayaran');
        Schema::dropIfExists('payment_gateway');
        Schema::dropIfExists('tagihan_detail');
        Schema::dropIfExists('tagihan');
        Schema::dropIfExists('pelanggan_router');
        Schema::dropIfExists('langganan');
        Schema::dropIfExists('pelanggan');
        Schema::dropIfExists('paket_internet');
        Schema::dropIfExists('routers');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mitra_id');
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn('is_active');
        });
        Schema::dropIfExists('mitra');
        Schema::dropIfExists('roles');
    }
};
