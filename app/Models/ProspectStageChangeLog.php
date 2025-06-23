<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProspectStageChangeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'prospect_id',
        'old_stage',
        'new_stage',
        'changed_by',
    ];

    // Relationships
    public function prospect()
    {
        return $this->belongsTo(Prospect::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
