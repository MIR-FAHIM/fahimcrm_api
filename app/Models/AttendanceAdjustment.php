<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Http\Controllers\NotificationController;

class AttendanceAdjustment extends Model
{
    use HasFactory;

    protected $table = 'attendance_adjustments';

    protected $fillable = [
        'attendance_id',
        'requested_time',
        'type',
        'user_id',
        'status',
        'approved_by',
        'is_active',
        'note',
        'is_late',
        'is_early',
    ];

    protected $casts = [
        'requested_time' => 'datetime',
        'is_active'      => 'boolean',
        'is_late'        => 'boolean',
        'is_early'       => 'boolean',
    ];
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

}
