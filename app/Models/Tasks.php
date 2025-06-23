<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tasks extends Model
{
    use HasFactory;

    // Define the table name explicitly (optional, as Laravel will default to 'tasks')
    protected $table = 'tasks';

    // Define the fillable attributes
    protected $fillable = [
        'task_title',
        'task_details',
        'priority_id',
        'task_type_id',
        'is_remind',
        'due_date',
        'project_id',
        'project_phase_id',
        'prospect_id',
        'created_by',
        'status_id',
        'department_id',
        'completion_percentage',
        'show_completion_percentage',
    ];

    // Define relationships (if applicable)
    // A task belongs to a priority
    public function priority()
    {
        return $this->belongsTo(Priority::class, 'priority_id');
    }
    public function assignedPersons()
    {
        return $this->hasMany(TaskAssignedPersons::class, 'task_id')->with('assignedPerson');
    }

    // A task belongs to a task type
    public function taskType()
    {
        return $this->belongsTo(TaskType::class, 'task_type_id');
    }

    // A task belongs to a project
    public function project()
    {
        return $this->belongsTo(Projects::class, 'project_id');
    }

    // A task is created by a user
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // A task has a status
    public function status()
    {
        return $this->belongsTo(TaskStatus::class, 'status_id');
    }

    // A task belongs to a department
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
    public function tasks()
    {
        return $this->hasMany(Tasks::class, 'project_id');
    }
    // Optionally, you can add a $hidden property to hide sensitive data
    // protected $hidden = ['created_at', 'updated_at'];
}
