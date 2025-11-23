<?php

namespace App\Jobs;

use App\Models\Application;
use App\Services\CoolifyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProvisionApplicationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Timeout job (misal 5 menit karena build docker lama)
    public $timeout = 300;


    /**
     * Create a new job instance.
     */
    public function __construct(
        public Application $application
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Mulai provisioning aplikasi: " . $this->application->name);

        // Ubah status jadi provisioning
        $this->application->update(['status' => 'provisioning']);

        try {
            // Panggil Service (Logic API Coolify ada disini)
            CoolifyService::createApplication($this->application);

            Log::info("Sukses provisioning: " . $this->application->name);

        } catch (\Exception $e) {
            Log::error("Gagal provisioning: " . $e->getMessage());

            $this->application->update(['status' => 'failed']);

            // Opsional: Kirim notifikasi ke Admin/User kalau gagal
        }
    }
}
