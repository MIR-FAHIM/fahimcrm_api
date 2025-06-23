<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatMessage extends Model
{
   

    protected $fillable = [
        'conversation_room_id',
        'sender_id',
        'receiver_id',
        'message',
        'message_type',
        'file_path',
        'file_name',
        'file_size',
        'is_read',
        'is_delivered',
        'is_seen',
        'read_at',
        'delivered_at',
        'seen_at',
        'is_edited',
        'is_deleted',
        'parent_id'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_delivered' => 'boolean',
        'is_seen' => 'boolean',
        'is_edited' => 'boolean',
        'is_deleted' => 'boolean',
        'read_at' => 'datetime',
        'delivered_at' => 'datetime',
        'seen_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
       
    ];

    /**
     * Get the conversation room that owns the message.
     */
    public function conversationRoom(): BelongsTo
    {
        return $this->belongsTo(ConversationRoom::class);
    }

    /**
     * Get the sender of the message.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the receiver of the message.
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Get the parent message (for replies).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'parent_id');
    }

    /**
     * Get the replies to this message.
     */
    public function replies()
    {
        return $this->hasMany(ChatMessage::class, 'parent_id');
    }

    /**
     * Scope a query to only include messages of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('message_type', $type);
    }

    /**
     * Scope a query to only include unread messages.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope a query to only include messages in a specific conversation room.
     */
    public function scopeInRoom($query, $roomId)
    {
        return $query->where('conversation_room_id', $roomId);
    }

    /**
     * Mark the message as read.
     */
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now()
        ]);
    }

    /**
     * Mark the message as delivered.
     */
    public function markAsDelivered()
    {
        $this->update([
            'is_delivered' => true,
            'delivered_at' => now()
        ]);
    }

    /**
     * Mark the message as seen.
     */
    public function markAsSeen()
    {
        $this->update([
            'is_seen' => true,
            'seen_at' => now()
        ]);
    }

    /**
     * Get the file URL if this is a file message.
     */
    public function getFileUrlAttribute()
    {
        if ($this->message_type !== 'text' && $this->file_path) {
            return asset('storage/' . $this->file_path);
        }

        return null;
    }

    /**
     * Get the formatted created_at date.
     */
    public function getFormattedDateAttribute()
    {
        return $this->created_at->format('M d, Y h:i A');
    }

    /**
     * Get the formatted file size.
     */
    public function getFormattedFileSizeAttribute()
    {
        if ($this->file_size) {
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $bytes = max($this->file_size, 0);
            $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
            $pow = min($pow, count($units) - 1);
            $bytes /= (1 << (10 * $pow));

            return round($bytes, 2) . ' ' . $units[$pow];
        }

        return null;
    }
}
