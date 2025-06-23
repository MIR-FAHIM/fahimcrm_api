<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    protected $fillable = [
        'zone_name',
        'district_id',
        'division_id',
        'is_active',
    ];

    // Optional: relationships (if you have District and Division models)
    // public function district()
    // {
    //     return $this->belongsTo(District::class);
    // }

    // public function division()
    // {
    //     return $this->belongsTo(Division::class);
    // }
}
