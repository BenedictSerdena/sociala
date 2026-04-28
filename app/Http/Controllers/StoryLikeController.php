<?php

namespace App\Http\Controllers;

use App\Events\NewNotification;
use App\Models\Notification;
use App\Models\Story;

class StoryLikeController extends Controller
{
    public function toggle(Story $story)
    {
        $existing = $story->likes()->where('user_id', auth()->id())->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            $story->likes()->create(['user_id' => auth()->id()]);
            $liked = true;

            if ($story->user_id !== auth()->id()) {
                $notification = Notification::create([
                    'user_id' => $story->user_id,
                    'type' => 'story_like',
                    'data' => [
                        'message' => auth()->user()->name . ' liked your story',
                        'story_id' => $story->id,
                        'user_name' => auth()->user()->name,
                        'user_avatar' => auth()->user()->avatar_url,
                    ],
                ]);
                broadcast(new NewNotification($notification))->toOthers();
            }
        }

        return response()->json([
            'liked' => $liked,
            'count' => $story->likes()->count(),
        ]);
    }
}
