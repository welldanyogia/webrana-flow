<?php

namespace App\Providers;

use App\Models\Application;
use App\Models\Server; // [1] Import Model
use App\Observers\ApplicationObserver;
use App\Policies\ServerPolicy; // [1] Import Policy
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1. Registrasi Observer
        Application::observe(ApplicationObserver::class);

        // 2. Registrasi Policy Manual (Agar pasti terdeteksi)
        // Ini memberitahu Laravel: "Kalau ada yang akses Server::class, pakai aturan ServerPolicy::class"
        Gate::policy(Server::class, ServerPolicy::class);

        // 3. KUNCI MASTER: Bypass all policies for super_admin
        // Kode ini sudah benar, letakkan di paling bawah agar dieksekusi paling awal dalam chain
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
