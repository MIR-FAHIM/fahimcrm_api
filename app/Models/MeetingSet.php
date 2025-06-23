<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingSet extends Model
{
    // Table name (optional if it follows Laravel convention)
    protected $table = 'meeting_sets';

    // Allow mass assignment for these fields
    protected $fillable = [
        'meeting_title',
        'meeting_context',
        'task_id',
        'assign_to',
        'prospect_id',
        'meeting_type',
        'start_time',
        'notify_time',
        'status',
        'meeting_with',
        'priority_id',
    ];

    // Casts (optional: format timestamps as Carbon instances)
    protected $casts = [
        'start_time' => 'datetime',
        'notify_time' => 'datetime',
    ];

    // Relationships (optional, uncomment if needed)
    /*
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assign_to');
    }

    public function prospect()
    {
        return $this->belongsTo(Prospect::class);
    }

    public function priority()
    {
        return $this->belongsTo(Priority::class);
    }
    */
}
