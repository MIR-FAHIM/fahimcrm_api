<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FbSheetLeads extends Model
{
    protected $fillable = [
        'lead_id','full_name','phone_number','email','created_time',
        'ad_id','ad_name','adset_id','adset_name','campaign_id','campaign_name',
        'form_id','form_name','is_organic','platform','lead_status','raw'
    ];
    protected $casts = [
        'created_time' => 'datetime',
        'is_organic'   => 'boolean',
        'raw'          => 'array',
    ];
}
