<?php

namespace App\Events;

use App\Models\Story;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StoryPosted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Story $story, public int $recipientId) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('feed.' . $this->recipientId),
        ];
    }

    public function broadcastWith(): array
    {
        $story = $this->story;
        $user = $story->user;

        return [
            'id' => $story->id,
            'image_url' => $story->image_url,
            'created_at' => $story->created_at,
            'expires_at' => $story->expires_at,
            'likes_count' => 0,
            'is_liked' => false,
            'views_count' => 0,
            'user' => array_merge($user->toArray(), ['avatar_url' => $user->avatar_url]),
        ];
    }
}
