<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacebookLeads extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'mobile',
        'note',
        'ad_name',
        'type',
        'product_id',
        'status',
        
    ];

    public function product()
    {
        return $this->belongsTo(ProductItem::class, 'product_id');
    }
}
