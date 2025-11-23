<?php

namespace App\Observers;

use App\Jobs\ProvisionApplicationJob;
use App\Models\Application;
use App\Services\CoolifyService;

class ApplicationObserver
{
    /**
     * Handle the Application "created" event.
     */
    public function created(Application $application): void
    {
        // Jalankan di background (Queue) agar user tidak menunggu loading lama
        // Tapi untuk MVP, kita jalankan sync dulu (langsung)
        ProvisionApplicationJob::dispatch($application);
    }

    /**
     * Handle the Application "updated" event.
     */
    public function updated(Application $application): void
    {
        // Jika ada perubahan config, trigger redeploy (bisa buat job terpisah kalau mau)
        if ($application->wasChanged(['env_variables', 'build_pack', 'php_version'])) {
            // Logic sync env & redeploy
            // Sebaiknya dibuatkan Job terpisah: UpdateApplicationJob::dispatch($application);
            CoolifyService::syncEnv($application);
            CoolifyService::deploy($application);
        }
    }
    /**
     * Handle the Application "deleted" event.
     */
    public function deleted(Application $application): void
    {
        CoolifyService::deleteApplication($application);
    }
}
