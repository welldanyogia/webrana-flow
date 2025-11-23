<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Server;
use Illuminate\Support\Facades\Http;
use Filament\Notifications\Notification;
use Exception;

class CoolifyService
{
    /**
     * Helper untuk membuat HTTP Client yang sudah ter-autentikasi
     */
    protected static function client(Server $server)
    {
        // Pastikan URL tidak diakhiri slash '/'
        $baseUrl = rtrim($server->coolify_api_url, '/');

        return Http::withToken($server->coolify_api_token)
            ->baseUrl($baseUrl . '/api/v1') // Asumsi API version v1
            ->timeout(30)
            ->acceptJson();
    }

    /**
     * 1. CREATE: Membuat Project & Resource Baru di Coolify
     * Dijalankan saat user klik "Create" di Dashboard.
     */
    public static function createApplication(Application $app): void
    {
        $server = $app->server;
        $package = $app->user->subscription->package;

        try {
            // A. Create Project (Grouping) jika belum ada
            // Disini kita pakai logic simple: 1 User = 1 Project UUID di Coolify
            // Atau pakai default 'default' project

            // B. Create Application Resource
            $payload = [
                'project_uuid' => 'default',
                'server_uuid'  => 'localhost', // ID internal Coolify (biasanya localhost jika coolify diinstall di node yg sama)
                'name'         => $app->name,
                'git_repository' => $app->repository_url,
                'git_branch'   => $app->branch,
                'build_pack'   => $app->build_pack, // 'laravel', 'docker', etc
                'ports_exposes' => '80',

                // Inject Resource Limits dari Paket
                'limits_cpu' => $app->custom_cpu_limit ?? $package->cpu_limit,
                'limits_memory' => $app->custom_ram_limit ?? $package->ram_limit,
            ];

            $response = self::client($server)->post('/applications', $payload);

            if ($response->failed()) {
                throw new Exception('Gagal membuat app di Coolify: ' . $response->body());
            }

            $data = $response->json();

            // C. Simpan UUID dari Coolify ke Database Lokal
            // Ini PENTING agar kita bisa update/delete nantinya
            $app->update([
                'coolify_project_uuid' => $data['project_uuid'] ?? 'default',
                'coolify_resource_uuid' => $data['uuid'],
                'status' => 'provisioning'
            ]);

            // D. Shared Database Injection Logic
            if ($package->is_shared_db) {
                $dbName = 'db_' . $app->id;
                
                // 1. Provision Database (Create DB if not exists)
                self::provisionSharedDatabase($dbName);

                // 2. Inject Env Vars
                $sharedDbEnv = [
                    'DB_CONNECTION' => 'mysql',
                    'DB_HOST' => env('SHARED_DB_HOST'),
                    'DB_PORT' => '3306',
                    'DB_DATABASE' => $dbName,
                    'DB_USERNAME' => env('SHARED_DB_USER'),
                    'DB_PASSWORD' => env('SHARED_DB_PASSWORD'),
                ];

                // Merge dengan env variables yang sudah ada
                $currentEnv = $app->env_variables ?? [];
                $app->update([
                    'env_variables' => array_merge($currentEnv, $sharedDbEnv)
                ]);
            }

            // E. Push Environment Variables
            self::syncEnv($app);

            // F. Trigger Initial Deploy
            self::deploy($app);

            Notification::make()->title('Aplikasi dibuat di server')->success()->send();

        } catch (Exception $e) {
            // Log error & Beritahu Admin
            Notification::make()->title('Error Provisioning')->body($e->getMessage())->danger()->send();
            // Opsional: Revert database lokal jika gagal
            // $app->delete();
        }
    }

    /**
     * Provision Shared Database
     * Membuat database baru di server shared DB
     */
    private static function provisionSharedDatabase(string $dbName): void
    {
        // Konfigurasi koneksi on-the-fly
        config(['database.connections.shared_provisioning' => [
            'driver' => 'mysql',
            'host' => env('SHARED_DB_HOST'),
            'port' => '3306',
            'database' => null, // Koneksi tanpa DB dulu untuk create DB
            'username' => env('SHARED_DB_USER'),
            'password' => env('SHARED_DB_PASSWORD'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]]);

        try {
            \Illuminate\Support\Facades\DB::connection('shared_provisioning')->statement("CREATE DATABASE IF NOT EXISTS `{$dbName}`");
        } catch (Exception $e) {
            throw new Exception("Gagal membuat database shared: " . $e->getMessage());
        }
    }

    /**
     * 2. DEPLOY: Trigger Build & Deploy
     */
    public static function deploy(Application $app): void
    {
        if (!$app->coolify_resource_uuid) return;

        try {
            $response = self::client($app->server)->post("/applications/{$app->coolify_resource_uuid}/deploy", [
                'force' => true // Force rebuild
            ]);

            if ($response->successful()) {
                $deploymentUuid = $response->json()['deployment_uuid'] ?? null;

                $app->update(['status' => 'building']);

                Notification::make()
                    ->title('Deployment dimulai')
                    ->body("Build ID: $deploymentUuid")
                    ->success()
                    ->send();
            }
        } catch (Exception $e) {
            Notification::make()->title('Gagal Deploy')->body($e->getMessage())->danger()->send();
        }
    }

    /**
     * 3. REDEPLOY: Wrapper untuk tombol di Filament
     */
    public static function redeploy(Application $app): void
    {
        self::deploy($app);
    }

    /**
     * 4. SYNC ENV: Mengirim .env dari Laravel ke Coolify
     */
    public static function syncEnv(Application $app): void
    {
        if (!$app->coolify_resource_uuid || empty($app->env_variables)) return;

        // Format API Coolify biasanya butuh string text, bukan JSON
        // Kita ubah array KeyValue menjadi string "KEY=VALUE\n"
        $envString = "";
        foreach ($app->env_variables as $key => $value) {
            $envString .= "$key=$value\n";
        }

        self::client($app->server)->patch("/applications/{$app->coolify_resource_uuid}", [
            'env' => $envString
        ]);
    }

    /**
     * 5. DELETE: Menghapus Container saat user menghapus App
     */
    public static function deleteApplication(Application $app): void
    {
        if (!$app->coolify_resource_uuid) return;

        try {
            self::client($app->server)->delete("/applications/{$app->coolify_resource_uuid}");
        } catch (Exception $e) {
            // Silent error (biar user tetap bisa delete data lokal meski server error)
            logger()->error("Gagal hapus di coolify: " . $e->getMessage());
        }
    }
}
