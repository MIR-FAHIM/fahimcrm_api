<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProspectStage extends Model
{
    protected $table = 'prospect_stages';

    protected $fillable = [
        'stage_name',
        'is_active',
        'color_code',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function prospects()
{
    return $this->hasMany(Prospect::class, 'stage_id');
}
}
