<?php

// app/Models/ProductItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductItem extends Model
{
    protected $fillable = [
        'product_name',
        'description',
        'is_active',
        'category_id',
        'image',
        'brand_id',
    ];

    public function variants()
    {
        return $this->hasMany(ProductVarient::class, 'product_id');
    }
}

