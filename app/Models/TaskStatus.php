<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskStatus extends Model
{
    protected $fillable = [
        'status_name',
        'department_id',
        'isActive',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

}
