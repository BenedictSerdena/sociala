<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class CallSignal implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public int    $toUserId,
        public string $type,        // 'incoming' | 'accepted' | 'declined' | 'ended'
        public string $callType,    // 'voice' | 'video'
        public string $channelName,
        public array  $caller,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('call.' . $this->toUserId);
    }

    public function broadcastAs(): string
    {
        return 'CallSignal';
    }
}
