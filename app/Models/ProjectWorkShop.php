<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectWorkShop extends Model
{
    use HasFactory;

    // Table name (optional if it follows convention)
    protected $table = 'project_work_shops';

    // Allow mass assignment for these fields
    protected $fillable = [
        'title',
        'content',
        'url',
        'status',
        'created_by',
        'project_id',
        'type',
        'is_active',
    ];

    /**
     * Relationships
     */

    // A workshop belongs to a user (creator)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // A workshop belongs to a project
    public function project()
    {
        return $this->belongsTo(Projects::class, 'project_id');
    }
}
