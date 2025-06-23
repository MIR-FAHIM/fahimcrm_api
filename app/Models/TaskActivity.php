<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskActivity extends Model
{
    use HasFactory;

    // Define the table name if it's different from the plural form of the model
    protected $table = 'task_activities';

    // Specify the fields that are mass assignable
    protected $fillable = [
        'task_id', 
        'activity_title', 
        'activity_details', 
        'status', 
        'type', 
        'created_by'
    ];

    // Define the relationship with the Task model
    public function task()
    {
        return $this->belongsTo(Tasks::class);
    }

    // Define the relationship with the User model (creator of the activity)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
