<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactUSForm extends Model
{
    protected $table = 'contact_u_s_forms';

    protected $fillable = [
        'person_name',
        'email',
        'mobile',
        'type',
        'status',
        'campaign_id',
        'website',
        'additional_field_one',
        'additional_field_two',
        'query',
    ];

    // Optional: If you have a Campaign model and relationship
    // public function campaign()
    // {
    //     return $this->belongsTo(Campaign::class);
    // }
}
