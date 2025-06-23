<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
   protected $fillable = ['category_name', 'is_active'];
}
