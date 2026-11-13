<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agent B owned bounded contexts: Network Access projection and Network Accounting.
 *
 * Additive and reversible. No shared/business table is altered here.
 * FreeRADIUS native tables (radcheck/radreply/radacct/...) are NOT created by this
 * migration: they belong to the pinned FreeRADIUS version on the RADIUS database.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nas_devices', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 120);
            $table->string('management_ip', 45);
            $table->string('nas_identifier', 120)->nullable()->unique();
            $table->string('site_label', 120)->nullable();
            $table->string('routeros_version', 40)->nullable();
            $table->string('model', 80)->nullable();
            $table->string('status', 20)->default('active'); // active|maintenance|disabled
            $table->boolean('accounting_enabled')->default(false);
            $table->unsignedInteger('interim_interval_seconds')->default(300);
            $table->string('radius_secret_reference', 191)->nullable();
            $table->string('api_secret_reference', 191)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'accounting_enabled']);
        });

        Schema::create('network_access_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name', 120);
            $table->unsignedInteger('download_mbps');
            $table->unsignedInteger('upload_mbps');
            $table->string('mikrotik_rate_limit', 100)->nullable();
            $table->boolean('is_isolation')->default(false);
            $table->string('status', 20)->default('active'); // active|retired
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('effective_from')->nullable();
            $table->timestamps();

            $table->index(['status', 'version']);
        });

        Schema::create('network_access_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscription_id')->unique();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('partner_id')->nullable();
            $table->foreignId('nas_device_id')->nullable()->constrained('nas_devices')->nullOnDelete();
            $table->foreignId('profile_id')->nullable()->constrained('network_access_profiles')->nullOnDelete();
            $table->string('pppoe_username', 100)->unique();
            $table->string('credential_reference', 191)->nullable();
            $table->string('ip_pool', 100)->nullable();
            $table->string('desired_state', 20)->default('active'); // active|suspended|terminated
            $table->string('applied_state', 20)->nullable();
            $table->unsignedInteger('policy_version')->default(1);
            $table->timestamp('last_sync_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['customer_id']);
            $table->index(['partner_id', 'desired_state']);
            $table->index(['applied_state', 'desired_state']);
        });

        Schema::create('radius_sync_outbox', function (Blueprint $table) {
            $table->id();
            $table->string('aggregate_type', 40);
            $table->unsignedBigInteger('aggregate_id');
            $table->unsignedBigInteger('partner_id')->nullable();
            $table->string('command_type', 40); // provision|suspend|restore|change_profile|disconnect
            $table->json('payload_json');
            $table->unsignedInteger('policy_version')->default(1);
            $table->string('idempotency_key', 191)->unique();
            $table->string('payload_hash', 64);
            $table->string('correlation_id', 64)->nullable();
            $table->string('status', 20)->default('pending'); // pending|processing|succeeded|failed|dead_letter|superseded
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('available_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['status', 'available_at']);
            $table->index(['aggregate_type', 'aggregate_id', 'policy_version']);
            $table->index(['partner_id', 'status']);
        });

        Schema::create('network_policy_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_type', 40)->default('user');
            $table->string('entity_type', 60);
            $table->unsignedBigInteger('entity_id');
            $table->string('action', 60);
            $table->unsignedInteger('old_policy_version')->nullable();
            $table->unsignedInteger('new_policy_version')->nullable();
            $table->string('old_profile_code', 60)->nullable();
            $table->string('new_profile_code', 60)->nullable();
            $table->string('result', 30);
            $table->foreignId('nas_device_id')->nullable()->constrained('nas_devices')->nullOnDelete();
            $table->string('reason', 191)->nullable();
            $table->string('correlation_id', 64)->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['created_at']);
        });

        Schema::create('radius_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nas_device_id')->constrained('nas_devices')->restrictOnDelete();
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('partner_id')->nullable();
            $table->string('username', 100);
            $table->string('acct_session_id', 120);
            $table->string('acct_unique_id', 120)->nullable();
            $table->string('framed_ip_address', 45)->nullable();
            $table->timestamp('start_at');
            $table->timestamp('last_sample_at')->nullable();
            $table->timestamp('stop_at')->nullable();
            $table->unsignedInteger('session_seconds')->nullable();
            $table->unsignedBigInteger('input_bytes_64')->default(0);
            $table->unsignedBigInteger('output_bytes_64')->default(0);
            $table->string('terminate_cause', 60)->nullable();
            $table->string('status', 20)->default('active'); // active|closed|stale
            $table->boolean('estimated_close')->default(false);
            $table->string('close_reason', 60)->nullable();
            $table->string('raw_record_reference', 191)->nullable();
            $table->timestamps();

            // Session-id reuse safety: a restart of the same Acct-Session-Id has a different start_at.
            $table->unique(['nas_device_id', 'acct_session_id', 'start_at']);
            $table->index(['customer_id', 'start_at']);
            $table->index(['partner_id', 'start_at']);
            $table->index(['status', 'last_sample_at']);
        });

        Schema::create('radius_accounting_samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('radius_session_id')->constrained('radius_sessions')->cascadeOnDelete();
            $table->foreignId('nas_device_id')->constrained('nas_devices')->restrictOnDelete();
            $table->string('username', 100);
            $table->string('session_key', 191);
            $table->string('event_type', 20); // start|interim|stop
            $table->timestamp('sampled_at');
            $table->unsignedBigInteger('input_total_bytes')->default(0);
            $table->unsignedBigInteger('output_total_bytes')->default(0);
            $table->unsignedBigInteger('input_delta_bytes')->default(0);
            $table->unsignedBigInteger('output_delta_bytes')->default(0);
            $table->boolean('delta_valid')->default(true);
            $table->boolean('counter_reset_detected')->default(false);
            $table->boolean('out_of_order')->default(false);
            $table->string('dedupe_key', 64)->unique();
            $table->string('source_event_id', 191)->nullable();
            $table->timestamp('ingested_at');
            $table->timestamps();

            $table->index(['radius_session_id', 'sampled_at']);
            $table->index(['sampled_at', 'event_type']);
        });

        Schema::create('customer_usage_daily', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('partner_id')->nullable();
            $table->date('usage_date');
            $table->unsignedBigInteger('upload_bytes')->default(0);
            $table->unsignedBigInteger('download_bytes')->default(0);
            $table->unsignedBigInteger('total_bytes')->default(0);
            $table->unsignedBigInteger('covered_seconds')->default(0);
            $table->unsignedInteger('sample_count')->default(0);
            $table->string('completeness', 20)->default('complete'); // complete|estimated|gap
            $table->unsignedInteger('calculation_version')->default(1);
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->unique(['customer_id', 'usage_date']);
            $table->index(['partner_id', 'usage_date']);
        });

        Schema::create('partner_usage_daily', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->date('usage_date');
            $table->unsignedBigInteger('upload_bytes')->default(0);
            $table->unsignedBigInteger('download_bytes')->default(0);
            $table->unsignedBigInteger('total_bytes')->default(0);
            $table->decimal('peak_download_mbps', 12, 3)->nullable();
            $table->decimal('peak_upload_mbps', 12, 3)->nullable();
            $table->string('completeness', 20)->default('complete');
            $table->unsignedInteger('calculation_version')->default(1);
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->unique(['partner_id', 'usage_date']);
        });

        Schema::create('router_queue_samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nas_device_id')->constrained('nas_devices')->restrictOnDelete();
            $table->unsignedBigInteger('partner_id')->nullable();
            $table->string('queue_key', 120);
            $table->timestamp('sampled_at');
            $table->unsignedBigInteger('current_rate_down_bps')->default(0);
            $table->unsignedBigInteger('current_rate_up_bps')->default(0);
            $table->unsignedBigInteger('bytes_down_total')->default(0);
            $table->unsignedBigInteger('bytes_up_total')->default(0);
            $table->unsignedInteger('sample_interval_seconds')->default(60);
            $table->string('dedupe_key', 64)->unique();
            $table->timestamps();

            $table->index(['partner_id', 'sampled_at']);
            $table->index(['nas_device_id', 'queue_key', 'sampled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('router_queue_samples');
        Schema::dropIfExists('partner_usage_daily');
        Schema::dropIfExists('customer_usage_daily');
        Schema::dropIfExists('radius_accounting_samples');
        Schema::dropIfExists('radius_sessions');
        Schema::dropIfExists('network_policy_audits');
        Schema::dropIfExists('radius_sync_outbox');
        Schema::dropIfExists('network_access_assignments');
        Schema::dropIfExists('network_access_profiles');
        Schema::dropIfExists('nas_devices');
    }
};
