<?php

namespace App\Events\user;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessageChatEvent implements ShouldBroadcast
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
        $reciever = $this->data->user_id1 === $this->data->lastChatMessage->message->user_send_id ? $this->data->user_id2 : $this->data->user_id1;
        return [
            new Channel('chat.'.$reciever),
        ];
    }

    public function broadcastAs()
    {
        return 'receive-message';
    }
}
