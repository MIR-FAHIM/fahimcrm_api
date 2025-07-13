<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = [
        'order_id',
        'status',
        'product_id',
        'quantity',
        'product_amount',
        'discount',
        'total_amount',
        'remark',
        'created_by',
    ];

    // Relationships

    public function product()
    {
        return $this->belongsTo(ProductItem::class, 'product_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function order()
    {
        return $this->belongsTo(ProductOrder::class, 'order_id');
    }
}
