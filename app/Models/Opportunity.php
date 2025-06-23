<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Opportunity extends Model
{
    protected $fillable = [
        'details',
        'prospect_id',
        'created_by',
        'closing_date',
        'expected_amount',
        'priority_id',
        'stage_id',
        'approved_by',
        'status',
        'note',
    ];

    // Optional: Define relationships if needed

    public function prospect()
    {
        return $this->belongsTo(Prospect::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function priority()
    {
        return $this->belongsTo(Priority::class);
    }

    public function stage()
    {
        return $this->belongsTo(ProspectStage::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
