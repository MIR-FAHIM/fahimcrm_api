<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductVarient extends Model
{
    use HasFactory;

    protected $table = 'product_variants';

    protected $fillable = [
        'product_id',
        'type',
        'model',
        'sku',
        'product_code',
        'quantity_required',
        'stock_id',
        'is_active',
        'status',
        'discount',
        'price',
        'vat',
        'discount_type',
        'color_code',
        'size',
        'unit',
        'weight',
        'entry_by',
        'discount_start_date',
        'discount_end_date',
        'is_refundable',
        'video_link',
        'image_link',
        'cover_image',
        'external_link',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_refundable' => 'boolean',
        'discount_start_date' => 'date',
        'discount_end_date' => 'date',
        'price' => 'decimal:2',
        'discount' => 'decimal:2',
        'vat' => 'decimal:2',
        'weight' => 'decimal:2',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(ProductItem::class);
    }

    public function entryBy()
    {
        return $this->belongsTo(User::class, 'entry_by');
    }
}
