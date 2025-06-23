<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndustryType extends Model
{
  
    protected $table = 'industry_types';

    protected $fillable = [
        'industry_type_name',
        'is_active',
       
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
