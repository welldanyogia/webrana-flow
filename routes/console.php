<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;
use App\Models\Server;
use App\Jobs\MonitorServerJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    $servers = Server::all();
    foreach ($servers as $server) {
        MonitorServerJob::dispatch($server);
    }
})->everyFiveMinutes();
