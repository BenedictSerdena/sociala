<?php

namespace App\Http\Controllers;

use App\Events\NewNotification;
use App\Models\Notification;
use App\Models\Post;

class LikeController extends Controller
{
    public function toggle(Post $post)
    {
        $existing = $post->likes()->where('user_id', auth()->id())->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            $post->likes()->create(['user_id' => auth()->id()]);
            $liked = true;

            if ($post->user_id !== auth()->id()) {
                $notification = Notification::create([
                    'user_id' => $post->user_id,
                    'type' => 'like',
                    'data' => [
                        'message' => auth()->user()->name . ' liked your post',
                        'post_id' => $post->id,
                        'user_name' => auth()->user()->name,
                        'user_avatar' => auth()->user()->avatar_url,
                    ],
                ]);
                broadcast(new NewNotification($notification))->toOthers();
            }
        }

        return response()->json([
            'liked' => $liked,
            'count' => $post->likes()->count(),
        ]);
    }
}
