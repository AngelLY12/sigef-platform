<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdministrationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $amount;
    public int $id;
    public string $concept_name;
    public string $action;
    /**
     * Create a new event instance.
     */
    public function __construct(string $amount, int $id, string $concept_name, string $action)
    {
        $this->amount = $amount;
        $this->id = $id;
        $this->concept_name = $concept_name;
        $this->action = $action;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
