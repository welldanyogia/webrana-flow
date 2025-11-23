<?php

use App\Http\Controllers\GitHubController;
use Illuminate\Support\Facades\Route;

//Route::get('/', function () {
//    return view('welcome');
//});

// Route khusus untuk Connect GitHub (Harus Login Dulu)
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/auth/github/redirect', [GitHubController::class, 'redirect'])->name('github.connect');
    Route::get('/auth/github/callback', [GitHubController::class, 'callback']);
});

Route::get('/ping-server', function() {
    return response()->json([
        'status' => 'alive',
        'domain' => request()->getHost(),
        'scheme' => request()->getScheme(),
        'ip' => request()->ip(),
        'secure' => request()->secure(),
    ]);
});
