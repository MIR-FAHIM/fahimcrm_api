<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notifications extends Model
{
    use HasFactory;

    // Define the table name if it's different from the default (notifications)
    protected $table = 'notifications';

    // Define the fillable attributes to allow mass assignment
    protected $fillable = [
        'title',
        'subtitle',
        'is_seen',
        'send_push',
        'type',
        'user_id',
    ];

    // Define the relationship with the User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accessor for the 'is_seen' attribute.
     *
     * @param  bool  $value
     * @return string
     */
    public function getIsSeenAttribute($value)
    {
        return (bool) $value;
    }

    /**
     * Accessor for the 'send_push' attribute.
     *
     * @param  bool  $value
     * @return string
     */
    public function getSendPushAttribute($value)
    {
        return (bool) $value;
    }

    // You can also add any additional methods you need here
}
