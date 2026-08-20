<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'designation_id',
        'role_id',
        'department_id',
        'birthdate',
        'isActive',
        'photo',
        'bio',
        'fcm_token',
        'attendance_method_id',
        'birthdate',
        'password',
        'start_hour',
        'start_min',
        'end_hour',
        'end_min',
        'is_dark_mode',
        'is_prospect_table_view',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birthdate' => 'date', // Casting birthdate as date
            'isActive' => 'boolean', // Casting isActive as boolean
            'is_dark_mode' => 'boolean',
            'is_prospect_table_view' => 'boolean',
        ];
    }

    public function attendanceMethod()
    {
        return $this->belongsTo(AttendanceMethod::class);
    }

    /**
     * Define the relationship with the Designation model.
     */
    public function designation()
    {
        return $this->belongsTo(Designations::class);
    }

    /**
     * Define the relationship with the Role model.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Define the relationship with the Department model.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
