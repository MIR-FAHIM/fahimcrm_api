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
        'source',
        'external_ticket_id',
        'external_client_id',
        'external_client_name',
        'external_client_email',
        'external_client_phone',
        'match_status',
        'matched_by',
        'converted_task_id',
        'external_priority',
        'external_status',
        'raw_payload',
        'last_synced_at',
    ];

    protected $casts = [
        'is_urgent' => 'boolean',
        'is_completed' => 'boolean',
        'raw_payload' => 'array',
        'last_synced_at' => 'datetime',
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

    public function convertedTask()
    {
        return $this->belongsTo(Tasks::class, 'converted_task_id');
    }
}
