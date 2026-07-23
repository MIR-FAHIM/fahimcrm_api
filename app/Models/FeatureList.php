<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureList extends Model
{
    // Table name (optional if it matches the class name)
    protected $table = 'feature_lists';

    // Mass assignable fields
    protected $fillable = [
        'module',
        'feature_name',
        'feature_key',
        'details',
        'is_active',
    ];
}
