<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'allow_custom_domain' => 'boolean',
        'allow_auto_backup' => 'boolean',
        'allow_high_availability' => 'boolean',
        'is_featured' => 'boolean',
    ];
}
