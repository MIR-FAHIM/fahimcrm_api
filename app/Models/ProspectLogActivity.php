<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProspectLogActivity extends Model
{
    protected $fillable = [
        'prospect_id',
        'related_id',
        'activity_type',
        'title',
        'notes',
        'activity_time',
        'created_by',
    ];

    // Relationships
    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function relatedTask(): BelongsTo
    {
        return $this->belongsTo(Tasks::class, 'related_id');
    }
}
