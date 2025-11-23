<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. INFRASTRUCTURE LAYER
        // Menyimpan data VPS (Engine Coolify) dan Mirroring
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "DO-SG-Primary"
            $table->string('provider'); // 'digitalocean', 'vultr', 'google'
            $table->string('ip_address');
            $table->string('private_ip_address')->nullable(); // Untuk replikasi database internal

            // Koneksi ke Coolify API
            $table->string('coolify_api_url');
            $table->text('coolify_api_token'); // Wajib di-encrypt di Model

            // Load Balancing & Status
            $table->integer('max_capacity_apps')->default(50);
            $table->integer('current_apps_count')->default(0);
            $table->enum('status', ['active', 'maintenance', 'locked', 'unreachable'])->default('active');

            // MIRRORING / HIGH AVAILABILITY CONFIG
            // Server ini nge-mirror siapa? (Self-referencing)
            $table->foreignId('mirror_of_server_id')->nullable()->constrained('servers')->nullOnDelete();
            $table->enum('role', ['primary', 'standby'])->default('primary');

            $table->timestamps();
        });

        // 2. PRODUCT & BILLING LAYER
        // Definisi Paket Jualan
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "Dev Starter", "Pro Apps"
            $table->string('slug')->unique();
            $table->decimal('price_monthly', 12, 2);
            $table->decimal('price_yearly', 12, 2);

            // Resource Limits (Dikirim ke Coolify)
            $table->integer('max_applications');
            $table->integer('max_databases');
            $table->string('cpu_limit')->default('0.5'); // Docker cpus
            $table->string('ram_limit')->default('256M'); // Docker memory

            // Fitur Premium Flags
            $table->boolean('allow_custom_domain')->default(false);
            $table->boolean('allow_auto_backup')->default(false);
            $table->boolean('allow_high_availability')->default(false); // Fitur Mirroring berbayar
            $table->boolean('is_featured')->default(false);

            $table->timestamps();
        });

        // Data Langganan User
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained();

            $table->string('status'); // 'active', 'cancelled', 'past_due'
            $table->string('payment_gateway_ref')->nullable(); // ID Transaksi Midtrans

            $table->timestamp('starts_at')->useCurrent();
            $table->timestamp('ends_at')->nullable();

            $table->timestamps();
        });

        // 3. AUTHENTICATION LAYER
        // Token GitHub/GitLab User
        Schema::create('git_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider'); // 'github', 'gitlab'
            $table->string('username'); // username github
            $table->string('provider_id'); // ID unik dari github
            $table->text('access_token'); // ENCRYPTED!
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamps();
        });

        // 4. CORE ENGINE LAYER
        // Aplikasi Laravel User
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('server_id')->constrained(); // App ini hidup di server mana?
            $table->foreignId('git_account_id')->constrained(); // Konek pake akun git mana?

            // Identitas Project
            $table->string('name');
            $table->string('repository_url'); // "user/repo-name"
            $table->string('branch')->default('main');

            // Coolify Internal Identifiers (Penting untuk API Call)
            $table->string('coolify_project_uuid')->nullable();
            $table->string('coolify_resource_uuid')->nullable();

            // Environment & Config
            $table->string('build_pack')->default('laravel');
            $table->json('env_variables')->nullable(); // Disimpan terenkripsi
            $table->string('php_version')->default('8.2');

            // STATELESS CONFIG (Fitur Anti-Banned)
            $table->boolean('is_stateless')->default(false);
            $table->string('s3_bucket_name')->nullable(); // Jika user pakai S3 eksternal

            $table->enum('status', ['provisioning', 'running', 'stopped', 'building', 'failed', 'migrating'])->default('provisioning');

            $table->timestamps();
            $table->softDeletes();
        });

        // Domain Management (Subdomain & Custom)
        Schema::create('app_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();

            $table->string('domain_name'); // e.g. "project.webrana.id" atau "tokosaya.com"
            $table->enum('type', ['system_subdomain', 'custom_domain']);

            // Logic Verifikasi DNS
            $table->boolean('is_verified')->default(false);
            $table->string('verification_token')->nullable(); // TXT Record
            $table->timestamp('verified_at')->nullable();

            $table->boolean('is_primary')->default(false);
            $table->enum('ssl_status', ['pending', 'active', 'failed'])->default('pending');

            $table->timestamps();
        });

        // 5. LOGS & AUTOMATION LAYER
        // Log Deployment (Build Logs)
        Schema::create('deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();

            $table->string('coolify_deployment_uuid')->nullable();
            $table->string('commit_hash')->nullable();
            $table->string('commit_message')->nullable();

            $table->enum('status', ['queued', 'in_progress', 'finished', 'failed']);
            $table->longText('build_logs')->nullable();
            $table->integer('duration_seconds')->nullable();

            $table->timestamps();
        });

        // Log Backup Otomatis
        Schema::create('app_backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();

            $table->enum('type', ['database', 'files']);
            $table->string('s3_path'); // Lokasi aman di luar DO
            $table->bigInteger('size_bytes')->default(0);

            $table->string('trigger'); // 'scheduled', 'manual', 'pre-migration'
            $table->enum('status', ['pending', 'uploading', 'completed', 'failed']);

            $table->timestamps();
        });

        // Log Migrasi Antar Server (Fitur Anti-Banned)
        Schema::create('server_migrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();

            $table->foreignId('source_server_id')->constrained('servers');
            $table->foreignId('target_server_id')->constrained('servers');

            // Tracking Step-by-Step
            $table->boolean('step_provision_target')->default(false);
            $table->boolean('step_sync_database')->default(false);
            $table->boolean('step_sync_storage')->default(false);
            $table->boolean('step_switch_dns')->default(false);

            $table->enum('status', ['queued', 'processing', 'completed', 'failed_reverted']);
            $table->text('error_log')->nullable();

            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_migrations');
        Schema::dropIfExists('app_backups');
        Schema::dropIfExists('deployments');
        Schema::dropIfExists('app_domains');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('git_accounts');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('packages');
        Schema::dropIfExists('servers');
    }
};
