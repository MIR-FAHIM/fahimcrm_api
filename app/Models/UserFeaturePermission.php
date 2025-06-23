<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserFeaturePermission extends Model
{
    // Table name (optional if it matches Laravel's naming convention)
    protected $table = 'user_feature_permissions';

    // Mass assignable fields
    protected $fillable = [
        'feature_id',
        'user_id',
        'has_permission',
    ];

    // Optional: relationships
    public function feature()
    {
        return $this->belongsTo(FeatureList::class, 'feature_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
