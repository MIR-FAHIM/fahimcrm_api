<?php
namespace App\Services;

use App\Events\RealtimeEvent;

class PusherService
{
    /**
     * Trigger a real-time event
     *
     * @param string $channel
     * @param string $event
     * @param array $data
     * @return void
     */
    public static function trigger(string $channel, string $event, array $data)
    {
        broadcast(new RealtimeEvent($channel, $event, $data));
    }
}