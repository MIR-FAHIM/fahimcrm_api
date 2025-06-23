<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'check_in_time',
        'check_in_location',
        'is_late',
        'is_work_from_home',
        'check_in_lat',
        'late_reason',
        'early_leave_reason',
        'check_in_lon',
        'check_out_time',
        'check_out_lat',
        'check_out_lon',
        'check_out_location',
        'is_early_leave',
        'total_duration',
        'from_field',
    ];

    protected $casts = [
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'total_duration' => 'integer',  // Make sure this is in correct format
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

  
}
