<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table = 'clients';

    protected $fillable = [
        'prospect_id',
        'client_code',
        'status',
        'isActive',
    ];

    // Optional: define relationship if needed
    public function prospect()
    {
        return $this->belongsTo(Prospect::class);
    }
}
