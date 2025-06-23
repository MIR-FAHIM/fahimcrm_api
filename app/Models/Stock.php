<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $fillable = [
        'stock_name',
        'stock_date',
        'created_by',
        'status',
        'vendor_id',
        'quantity',
        'note',
        'stock_code',
    ];

    protected $casts = [
        'stock_date' => 'date',
    ];

    // Optional relationships
    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
