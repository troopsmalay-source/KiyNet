<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tagihan', function (Blueprint $table) {
            $table->string('idempotency_key', 120)->nullable()->unique()->after('periode');
            $table->timestamp('voided_at')->nullable()->after('paid_at');
            $table->text('void_reason')->nullable()->after('voided_at');
        });

        Schema::table('pembayaran', function (Blueprint $table) {
            $table->string('idempotency_key', 120)->nullable()->unique()->after('nomor_referensi');
        });

        Schema::create('invoice_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('tagihan')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('subscription_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('langganan_id')->constrained('langganan')->cascadeOnDelete();
            $table->string('type', 40);
            $table->foreignId('from_paket_internet_id')->nullable()->constrained('paket_internet')->nullOnDelete();
            $table->foreignId('to_paket_internet_id')->nullable()->constrained('paket_internet')->nullOnDelete();
            $table->date('effective_date')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['langganan_id', 'type']);
        });

        Schema::create('partner_bandwidth_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mitra_id')->constrained('mitra')->cascadeOnDelete();
            $table->unsignedInteger('committed_mbps');
            $table->bigInteger('price_idr_per_mbps')->default(0);
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->enum('status', ['draft', 'active', 'expired', 'terminated'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('partner_bandwidth_contract_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_bandwidth_contract_id')->constrained('partner_bandwidth_contracts')->cascadeOnDelete();
            $table->string('type', 40);
            $table->unsignedInteger('requested_mbps')->nullable();
            $table->string('reason', 255)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tax_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->unsignedInteger('rate_basis_points')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tax_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mitra_id')->constrained('mitra')->restrictOnDelete();
            $table->foreignId('tax_category_id')->constrained('tax_categories')->restrictOnDelete();
            $table->string('period', 7);
            $table->bigInteger('gross_amount_idr')->default(0);
            $table->bigInteger('tax_amount_idr')->default(0);
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'changes_requested'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['mitra_id', 'tax_category_id', 'period']);
        });

        Schema::create('tax_submission_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_submission_id')->constrained('tax_submissions')->cascadeOnDelete();
            $table->string('description', 200);
            $table->bigInteger('amount_idr')->default(0);
            $table->timestamps();
        });

        Schema::create('notification_outbox', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 30)->default('whatsapp');
            $table->string('recipient', 150);
            $table->string('template', 100);
            $table->json('variables')->nullable();
            $table->string('business_reference', 120)->nullable();
            $table->string('idempotency_key', 150)->unique();
            $table->enum('status', ['pending', 'sent', 'failed', 'dead_letter'])->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('available_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_outbox_id')->constrained('notification_outbox')->cascadeOnDelete();
            $table->unsignedSmallInteger('attempt');
            $table->enum('status', ['sent', 'failed']);
            $table->text('response')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pembayaran_id')->constrained('pembayaran')->restrictOnDelete();
            $table->bigInteger('amount_idr');
            $table->string('reason', 255);
            $table->enum('status', ['requested', 'approved', 'rejected', 'processed'])->default('requested');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        DB::table('tax_categories')->insert([
            ['code' => 'PPN', 'name' => 'Pajak Pertambahan Nilai', 'rate_basis_points' => 1100, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'PPH21', 'name' => 'PPh Pasal 21', 'rate_basis_points' => 500, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'KOMDIGI', 'name' => 'Kontribusi Komdigi Tahunan', 'rate_basis_points' => 0, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_refunds');
        Schema::dropIfExists('notification_attempts');
        Schema::dropIfExists('notification_outbox');
        Schema::dropIfExists('tax_submission_items');
        Schema::dropIfExists('tax_submissions');
        Schema::dropIfExists('tax_categories');
        Schema::dropIfExists('partner_bandwidth_contract_events');
        Schema::dropIfExists('partner_bandwidth_contracts');
        Schema::dropIfExists('subscription_events');
        Schema::dropIfExists('invoice_access_tokens');

        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn('idempotency_key');
        });

        Schema::table('tagihan', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn(['idempotency_key', 'voided_at', 'void_reason']);
        });
    }
};
