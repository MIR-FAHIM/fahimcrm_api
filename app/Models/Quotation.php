<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'prospect_id',
        'code',
        'isApproved',
        'reference_no',
        'created_by',
        'approved_by',
        'status',
        'total_amount',
        'note',
        'field_one',
        'field_two',
        'payment_terms',
        'tax',
        'discount',
        'quantity',
        'unit_price',
    ];

    // Optional: Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
