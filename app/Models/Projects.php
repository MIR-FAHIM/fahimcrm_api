<?php

namespace App\Models;

use Illuminate\Console\View\Components\Task;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Projects extends Model
{
    use HasFactory;

    // Define the table name explicitly (optional, as Laravel will default to 'projects')
    protected $table = 'projects';

    // Define the fillable attributes
    protected $fillable = [
        'project_name',
        'department_id',
        'is_tech',
        'is_marketing',
        'description',
        'created_by',
    ];

    // Define relationships (if applicable)
    // A project belongs to a department
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    // A project was created by a user
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function tasks()
    {
        return $this->hasMany(Tasks::class, 'project_id');
    }
    public function phases()
    {
        return $this->hasMany(ProjectPhase::class, 'project_id');
    }

    // Optionally, you can add a $hidden property to hide sensitive data
    // protected $hidden = ['created_at', 'updated_at'];
}
