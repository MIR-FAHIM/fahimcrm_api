<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Modulepermission extends Model
{
    // Table name (if it doesn't follow Laravel's default plural naming convention)
    protected $table = 'modulepermissions'; // optional if your table name follows Laravel's conventions

    // Define which fields can be mass-assigned
    protected $fillable = [
        'company_id', 
        'module', 
        'is_active',
    ];

    // Optionally, disable timestamps if you don't need them
    public $timestamps = true; // Or false if your table doesn't have created_at or updated_at columns
}
