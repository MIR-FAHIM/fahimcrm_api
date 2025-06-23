<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskAssignedPersons extends Model
{
    use HasFactory;

    // Define the table name explicitly (optional, as Laravel will default to 'task_assigned_persons')
    protected $table = 'task_assigned_persons';

    // Define the fillable attributes
    protected $fillable = [
        'assigned_person',
        'assigned_by',
        'is_main',
        'task_id',
    ];

    // Define relationships (if applicable)
    // For example, if you have a User model and a Task model:
    
    // Each TaskAssignedPerson belongs to a User
    public function assignedPerson()
    {
        return $this->belongsTo(User::class, 'assigned_person');
    }

    // Each TaskAssignedPerson belongs to a Task
    public function task()
    {
        return $this->belongsTo(Tasks::class, 'task_id');
    }

    // Optionally, you can add a $hidden property to hide sensitive data
    // protected $hidden = ['created_at', 'updated_at'];
}
