<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GitAccount extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'access_token' => 'encrypted', // Token GitHub dienkripsi
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
