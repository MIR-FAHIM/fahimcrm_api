<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'method',
        'ip_addresses',
        'latitude',
        'longitude',
        'radius_meters',
        'is_active',
    ];

    protected $casts = [
        'ip_addresses' => 'array',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'radius_meters' => 'integer',
        'is_active' => 'boolean',
    ];
}
