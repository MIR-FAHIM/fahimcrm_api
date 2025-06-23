<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductBrand extends Model
{
   protected $fillable = [
        'brand_name',
        'image',
        'is_active',
        'type',
        'added_by',
        'details',
    ];
}
