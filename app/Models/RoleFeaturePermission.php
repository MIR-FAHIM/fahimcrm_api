<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleFeaturePermission extends Model
{
    protected $table = 'role_feature_permissions';

    protected $fillable = [
        'role_id',
        'feature_id',
        'has_permission',
    ];

    protected $casts = [
        'has_permission' => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function feature()
    {
        return $this->belongsTo(FeatureList::class, 'feature_id');
    }
}