<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InformationSource extends Model
{
 
    protected $table = 'information_sources';

    protected $fillable = [
        'information_source_name',
        'is_active',
       
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
