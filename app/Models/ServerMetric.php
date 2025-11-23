<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServerMetric extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_online' => 'boolean',
        'cpu_usage' => 'float',
        'ram_usage' => 'float',
        'disk_usage' => 'float',
    ];

    public function server()
    {
        return $this->belongsTo(Server::class);
    }
}
