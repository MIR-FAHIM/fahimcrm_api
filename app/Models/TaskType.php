<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskType extends Model
{
    protected $fillable = [
        'type_name',
        'department_id',
        'isActive',
    ];
}
