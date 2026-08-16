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
        'order_serial',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order_serial' => 'integer',
    ];

    public function prospects()
{
    return $this->hasMany(Prospect::class, 'stage_id');
}
}
