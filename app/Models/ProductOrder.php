<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOrder extends Model
{
    // Allow mass assignment on these fields
    protected $fillable = [
        'cart_id',
        'customer_id',
        'address',
        'phone',
        'note',
        'order_from',
        'created_by',
        'amount',
        'is_cod',
        'status',
        'isPaid',
    ];

    // Cast fields to appropriate data types
    protected $casts = [
        'is_cod' => 'boolean',
        'isPaid' => 'boolean',
        'amount' => 'decimal:2',
    ];

    // Example relationships (optional, based on your schema)
    // public function cart()
    // {
    //     return $this->belongsTo(Cart::class);
    // }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
