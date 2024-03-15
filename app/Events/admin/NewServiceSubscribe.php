<?php

namespace App\Events\admin;

use Illuminate\Broadcasting\Channel;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;


class NewServiceSubscribe implements ShouldBroadcast
{
    public $data;
    /**
     * Create a new event instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('service'),
        ];
    }

    public function broadcastAs()
    {
        return 'new-subscribe';
    }
}
