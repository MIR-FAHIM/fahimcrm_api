<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectTeamMates extends Model
{
    protected $fillable = [
        'employee_id',
        'project_id',
        'isActive',
        'role',
        'status',
        'notify_active',
    ];
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

}

