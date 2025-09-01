<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectFeatures extends Model
{
    use HasFactory;

    // Table name (optional if you follow Laravel convention "project_features")
    protected $table = 'project_features';

    // Mass-assignable fields
    protected $fillable = [
        'project_id',
        'feature_name',
        'description',
        'type',
        'status',
        'completion_percentage',
        'version',
        'note',
        'next_plan',
    ];

    // Casts for auto conversion
    protected $casts = [
        'completion_percentage' => 'integer',
    ];

    /**
     * Each feature belongs to a project.
     */
    public function project()
    {
        return $this->belongsTo(Projects::class);
    }
}
