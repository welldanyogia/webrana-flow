<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Application extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'env_variables' => 'encrypted:array', // Otomatis encrypt/decrypt array di database
        'is_stateless' => 'boolean',
    ];

    // INI YANG TADI ERROR (Relasi ke Server)
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    // Relasi ke User Pemilik
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke Akun Git
    public function gitAccount(): BelongsTo
    {
        return $this->belongsTo(GitAccount::class);
    }

    // Relasi ke Domain (Subdomain/Custom)
    public function domains(): HasMany
    {
        return $this->hasMany(AppDomain::class);
    }

    // Relasi ke History Deployment
    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }
}
