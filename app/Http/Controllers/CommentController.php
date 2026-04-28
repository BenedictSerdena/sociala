<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Notification;
use App\Events\NewNotification;
use App\Models\Post;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Post $post)
    {
        $comments = $post->comments()->with('user')->latest()->get();
        return response()->json($comments->map(fn($c) => array_merge($c->toArray(), [
            'user' => array_merge($c->user->toArray(), ['avatar_url' => $c->user->avatar_url]),
        ])));
    }

    public function store(Request $request, Post $post)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:500',
        ]);

        $comment = $post->comments()->create([
            'user_id' => auth()->id(),
            'content' => $validated['content'],
        ]);

        $comment->load('user');

        if ($post->user_id !== auth()->id()) {
            $notification = Notification::create([
                'user_id' => $post->user_id,
                'type' => 'comment',
                'data' => [
                    'message' => auth()->user()->name . ' commented on your post',
                    'post_id' => $post->id,
                    'user_name' => auth()->user()->name,
                    'user_avatar' => auth()->user()->avatar_url,
                ],
            ]);
            broadcast(new NewNotification($notification))->toOthers();
        }

        return back();
    }

    public function destroy(Comment $comment)
    {
        abort_if($comment->user_id !== auth()->id(), 403);
        $comment->delete();
        return back();
    }
}
