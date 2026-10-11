<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppVersion extends Model
{
    protected $fillable = [
        'app_name',
        'platform',
        'version_name',
        'version_code',
        'min_supported_version_code',
        'download_url',
        'playstore_url',
        'appstore_url',
        'release_notes',
        'is_force_update',
        'is_active',
        'created_by',
        'published_at',
    ];

    protected $casts = [
        'version_code' => 'integer',
        'min_supported_version_code' => 'integer',
        'is_force_update' => 'boolean',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];
}
