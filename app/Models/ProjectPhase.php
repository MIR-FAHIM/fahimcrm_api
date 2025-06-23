<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectPhase extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'phase_name',
        'phase_order_id',
        'description',
        'status',
        'priority',
        'start_date',
        'end_date',
        'phase_completion_percentage',
    ];

    /**
     * Relationship to Project model (assuming you have one).
     */
    public function project()
    {
        return $this->belongsTo(Projects::class);
    }
}
