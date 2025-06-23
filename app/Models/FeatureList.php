<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureList extends Model
{
    // Table name (optional if it matches the class name)
    protected $table = 'feature_lists';

    // Mass assignable fields
    protected $fillable = [
        'feature_name',
        'details',
        'is_active',
    ];
}
