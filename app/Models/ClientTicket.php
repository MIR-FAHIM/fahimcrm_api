<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientTicket extends Model
{
    protected $table = 'client_tickets';

    protected $fillable = [
        'client_id',
        'subject',
        'description',
        'type',
        'issue_id',
        'ticket_code',
        'status',
        'priority_id',
        'is_urgent',
        'category',
        'attachment',
        'is_completed',
        'createdBy',
    ];

    // Relationships (if needed)
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function priority()
    {
        return $this->belongsTo(Priority::class, 'priority_id');
    }
}
