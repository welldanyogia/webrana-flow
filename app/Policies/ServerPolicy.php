<?php

namespace App\Policies;

use App\Models\Server;
use App\Models\User;

class ServerPolicy
{
    /**
     * Bypass Global untuk Super Admin
     */
    public function before(User $user, string $ability): bool|null
    {
        // Kita paksa cek guard 'web' secara eksplisit
        if ($user->hasRole('super_admin', 'web')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        dd($user->getRoleNames(), $user->id);
        return $user->hasRole('super_admin', 'web');
    }

    public function view(User $user, Server $server): bool
    {
        return $user->hasRole('super_admin', 'web');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin', 'web');
    }

    public function update(User $user, Server $server): bool
    {
        return $user->hasRole('super_admin', 'web');
    }

    public function delete(User $user, Server $server): bool
    {
        return $user->hasRole('super_admin', 'web');
    }
}
