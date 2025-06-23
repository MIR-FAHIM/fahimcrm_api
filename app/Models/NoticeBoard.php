<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NoticeBoard extends Model
{
    use HasFactory;

    protected $table = 'notice_boards';

    protected $fillable = [
        'title',
        'notice',
        'is_active',
        'highlight',
        'color_code',
        'start_date',
        'end_date',
        'created_by',
        'type',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'highlight'   => 'boolean',
        'start_date'  => 'datetime',
        'end_date'    => 'datetime',
    ];
}
