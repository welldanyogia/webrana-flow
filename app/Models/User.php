<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ... traits lainnya

    // Relasi ke Aplikasi
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    // Relasi ke Subscription (Langganan)
    public function subscription(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        // Asumsi user cuma punya 1 subscription aktif dalam satu waktu
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    // Relasi ke Akun Git
    public function gitAccounts(): HasMany
    {
        return $this->hasMany(GitAccount::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // LOGGING DEBUG (Cek nanti di storage/logs/laravel.log)
        \Illuminate\Support\Facades\Log::info("Cek Akses Panel: " . $panel->getId() . " | User: " . $this->email . " | Role: " . $this->getRoleNames());

//        if ($panel->getId() === 'admin') {
//            // Coba paksa return true dulu untuk mengetes apakah ini sumber masalahnya
//            // return true;
//
//            return $this->hasRole('super_admin');
//        }
//
//        if ($panel->getId() === 'dashboard') {
//            return true;
//        }

        return true;
    }
}
