<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RealtimeEvent implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $data;
    public $channel;
    public $event;

    public function __construct($channel, $event, $data)
    {
        $this->channel = $channel;
        $this->event = $event;
        $this->data = $data;
    }

    public function broadcastOn()
    {
        return new Channel($this->channel); // Use PrivateChannel() if needed
    }

    public function broadcastAs()
    {
        return $this->event;
    }

    public function broadcastWith()
    {
        return $this->data;
    }
}
