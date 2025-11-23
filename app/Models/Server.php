<?php

namespace App\Models;

use App\Policies\ServerPolicy;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Server extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'coolify_api_token' => 'encrypted', // Token API wajib dienkripsi
        'ssh_private_key' => 'encrypted',
    ];

    // Register policy explicitly
    protected static function booted(): void
    {
        //
    }

    // Satu Server punya banyak Aplikasi
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(ServerMetric::class);
    }
}
