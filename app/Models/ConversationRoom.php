<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationRoom extends Model
{
    protected $fillable = [
        'prospect_id',
        'general_id',
        'project_id',
        'client_id',
        'type',
        'room_name',
        'cover_photo',
    ];
}
