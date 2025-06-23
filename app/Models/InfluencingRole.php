<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InfluencingRole extends Model
{
    protected $table = 'influencing_roles';

    protected $fillable = [
        'role_name',
        'description',
        'is_active',
    ];
}
