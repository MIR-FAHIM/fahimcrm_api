<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeLeave extends Model
{
    use HasFactory;

    // Table associated with the model
    protected $table = 'employee_leaves';

    // Mass assignable attributes
    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'duration',
        'details',
        'isHalf',
        'howManyVacationDay',
        'approved_by',
        'is_approve',
        'status',
    ];

    /**
     * Get the employee that owns the leave.
     */
    public function employee()
    {
        return $this->belongsTo(User::class); // Assuming you have an Employee model
    }

    /**
     * Get the leave type associated with the leave.
     */
    public function leaveType()
    {
        return $this->belongsTo(SetAllLeave::class); // Assuming you have a LeaveType model
    }

    /**
     * Get the user who approved the leave.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by'); // Assuming you have a User model for the approver
    }
}
