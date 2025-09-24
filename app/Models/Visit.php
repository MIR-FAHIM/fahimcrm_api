<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Visit extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'visits';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'purpose',
        'note',
        'status',
        'visit_type',
        'scheduled_at',
        'actual_start_at',
        'actual_end_at',
        'checkin_latitude',
        'checkin_longitude',
        'employee_id',
        'planner_id',
        'lead_id',
        'zone_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'scheduled_at' => 'datetime',
        'actual_start_at' => 'datetime',
        'actual_end_at' => 'datetime',
    ];

    /**
     * Get the employee assigned to the visit.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the user who planned the visit.
     */
    public function planner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'planner_id');
    }

    /**
     * Get the lead associated with the visit.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Prospect::class, 'lead_id');
    }

    /**
     * Get the zone of the visit.
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class, 'priority_id');
    }

    /**
     * Get the task visit relation associated with the visit.
     */
    public function taskVisitRelation(): HasOne
    {
        return $this->hasOne(TaskVisitRelation::class, 'visit_id', 'id');
    }
}