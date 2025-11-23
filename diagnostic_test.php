<?php

/**
 * Script Diagnostic untuk Debug 403 Forbidden
 * Jalankan: php artisan tinker < diagnostic_test.php
 */

echo "==========================================\n";
echo "DIAGNOSTIC TEST - 403 Forbidden Debug\n";
echo "==========================================\n\n";

// Test 1: Cek Authentication
echo "1. AUTHENTICATION TEST\n";
echo "   Simulating logged-in user (contoh: user ID 1)\n";

$user = \App\Models\User::find(1);
if (!$user) {
    echo "   ❌ User ID 1 tidak ditemukan. Ganti dengan ID user Anda yang login.\n";
    exit(1);
}

echo "   ✓ User found: {$user->email} (ID: {$user->id})\n\n";

// Test 2: Cek Roles
echo "2. ROLES TEST\n";
$roles = $user->getRoleNames();
echo "   Roles: " . ($roles->isEmpty() ? 'NONE' : $roles->implode(', ')) . "\n";
$hasSuperAdmin = $user->hasRole('super_admin');
echo "   Has 'super_admin': " . ($hasSuperAdmin ? '✓ YES' : '❌ NO') . "\n\n";

if (!$hasSuperAdmin) {
    echo "   🔴 MASALAH: User tidak punya role super_admin!\n";
    echo "   Fix: jalankan di tinker:\n";
    echo "   \$user = User::find(1);\n";
    echo "   \$user->assignRole('super_admin');\n\n";
}

// Test 3: Cek Policy Registration
echo "3. POLICY REGISTRATION TEST\n";
$policy = \Illuminate\Support\Facades\Gate::getPolicyFor(\App\Models\Server::class);
if ($policy) {
    echo "   ✓ Policy registered: " . get_class($policy) . "\n\n";
} else {
    echo "   ❌ NO POLICY REGISTERED!\n";
    echo "   This is likely the problem.\n\n";
}

// Test 4: Cek Gate::before
echo "4. GATE::BEFORE TEST\n";
echo "   Testing if Gate::before allows super_admin...\n";

// Simulate authentication
\Illuminate\Support\Facades\Auth::setUser($user);

$testAbilities = ['viewAny', 'view', 'create', 'update', 'delete'];
$allPassed = true;

foreach ($testAbilities as $ability) {
    $allowed = \Illuminate\Support\Facades\Gate::allows($ability, \App\Models\Server::class);
    $status = $allowed ? '✓ ALLOWED' : '❌ DENIED';
    echo "   {$ability}: {$status}\n";
    
    if (!$allowed) {
        $allPassed = false;
    }
}

echo "\n";

if ($allPassed) {
    echo "   ✅ All abilities ALLOWED - Gate::before working!\n\n";
} else {
    echo "   ❌ Some abilities DENIED - Gate::before NOT working!\n\n";
}

// Test 5: Detailed Inspection
echo "5. DETAILED INSPECTION\n";
$response = \Illuminate\Support\Facades\Gate::inspect('viewAny', \App\Models\Server::class);
echo "   Ability: viewAny\n";
echo "   Allowed: " . ($response->allowed() ? 'YES' : 'NO') . "\n";
echo "   Message: " . ($response->message() ?? 'No message') . "\n";
echo "   Code: " . ($response->code() ?? 'No code') . "\n\n";

// Test 6: Policy Method Direct Call
if ($policy) {
    echo "6. DIRECT POLICY TEST\n";
    echo "   Calling ServerPolicy->viewAny() directly...\n";
    
    try {
        $result = $policy->viewAny($user);
        echo "   Result: " . ($result ? '✓ TRUE' : '❌ FALSE') . "\n\n";
    } catch (\Exception $e) {
        echo "   ❌ ERROR: " . $e->getMessage() . "\n\n";
    }
}

// Test 7: Spatie Permission Cache
echo "7. SPATIE PERMISSION CACHE TEST\n";
$cacheKey = config('permission.cache.key');
$cacheStore = config('permission.cache.store', 'default');
echo "   Cache Key: {$cacheKey}\n";
echo "   Cache Store: {$cacheStore}\n";

if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
    echo "   ⚠️  Permission cache EXISTS\n";
    echo "   Run: php artisan permission:cache-reset\n\n";
} else {
    echo "   ✓ Permission cache cleared\n\n";
}

// Summary
echo "==========================================\n";
echo "SUMMARY & RECOMMENDATIONS\n";
echo "==========================================\n";

if (!$hasSuperAdmin) {
    echo "❌ User tidak punya role 'super_admin'\n";
    echo "   → Assign role dengan: \$user->assignRole('super_admin')\n\n";
}

if (!$policy) {
    echo "❌ ServerPolicy tidak terdaftar di Gate\n";
    echo "   → Pastikan AuthServiceProvider sudah registered\n";
    echo "   → Atau tambahkan Gate::policy() di AppServiceProvider\n\n";
}

if (!$allPassed) {
    echo "❌ Gate authorization gagal\n";
    echo "   → Pastikan Gate::before ada di AppServiceProvider\n";
    echo "   → Logout dan login ulang dari Filament\n";
    echo "   → Clear browser cache (Ctrl+Shift+Delete)\n\n";
}

if ($hasSuperAdmin && $policy && $allPassed) {
    echo "✅ Semua test PASSED!\n";
    echo "   Tapi masih 403? Coba:\n";
    echo "   1. Logout dan login ulang dari Filament\n";
    echo "   2. Clear browser cache (Ctrl+Shift+Delete)\n";
    echo "   3. Restart PHP server (php artisan serve)\n";
    echo "   4. Cek laravel.log untuk error message\n\n";
}

echo "Test selesai!\n";
