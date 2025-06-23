<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskFollowup extends Model
{
    use HasFactory;

    // Define the table name if it's different from the plural form of the model
    protected $table = 'task_followups';

    // Specify the fields that are mass assignable
    protected $fillable = [
        'task_id', 
        'followup_title', 
        'followup_details', 
        'type', 
        'status', 
        'created_by'
    ];

    // Define the relationship with the Task model
    public function task()
    {
        return $this->belongsTo(Tasks::class);
    }

    // Define the relationship with the User model (creator of the followup)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
