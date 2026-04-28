<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\Notification;
use App\Events\NewNotification;
use App\Models\Post;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Post $post)
    {
        $comments = $post->comments()
            ->with(['user', 'replies.user'])
            ->whereNull('parent_id')
            ->orderByRaw('pinned_at IS NULL ASC, pinned_at DESC, created_at DESC')
            ->get();

        return response()->json($comments->map(fn($c) => $this->fmt($c)));
    }

    public function store(Request $request, Post $post)
    {
        $validated = $request->validate([
            'content'   => 'required|string|max:500',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $comment = $post->comments()->create([
            'user_id'   => auth()->id(),
            'content'   => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        $comment->load('user');

        // Notify post owner for top-level comments only
        if (!($validated['parent_id'] ?? null) && $post->user_id !== auth()->id()) {
            $notification = Notification::create([
                'user_id' => $post->user_id,
                'type'    => 'comment',
                'data'    => [
                    'message'     => auth()->user()->name . ' commented on your post',
                    'post_id'     => $post->id,
                    'user_name'   => auth()->user()->name,
                    'user_avatar' => auth()->user()->avatar_url,
                ],
            ]);
            broadcast(new NewNotification($notification))->toOthers();
        }

        return response()->json($this->fmt($comment));
    }

    public function destroy(Comment $comment)
    {
        // Comment owner OR post owner can delete
        abort_if(
            $comment->user_id !== auth()->id() && $comment->post->user_id !== auth()->id(),
            403
        );

        $comment->replies()->delete();
        $comment->delete();

        return response()->json(['success' => true]);
    }

    public function pin(Comment $comment)
    {
        abort_if($comment->post->user_id !== auth()->id(), 403);
        $comment->update(['pinned_at' => $comment->pinned_at ? null : now()]);
        return response()->json(['pinned' => (bool) $comment->pinned_at]);
    }

    public function hide(Comment $comment)
    {
        abort_if($comment->post->user_id !== auth()->id(), 403);
        $comment->update(['hidden_at' => $comment->hidden_at ? null : now()]);
        return response()->json(['hidden' => (bool) $comment->hidden_at]);
    }

    public function report(Request $request, Comment $comment)
    {
        abort_if($comment->user_id === auth()->id(), 403);

        $request->validate([
            'reason' => 'nullable|string|in:spam,harassment,inappropriate,misinformation',
        ]);

        CommentReport::firstOrCreate(
            ['user_id' => auth()->id(), 'comment_id' => $comment->id],
            ['reason' => $request->input('reason', 'inappropriate')]
        );

        return response()->json(['success' => true]);
    }

    private function fmt(Comment $comment): array
    {
        return [
            'id'        => $comment->id,
            'content'   => $comment->content,
            'parent_id' => $comment->parent_id,
            'pinned_at' => $comment->pinned_at,
            'hidden_at' => $comment->hidden_at,
            'created_at'=> $comment->created_at,
            'user'      => array_merge($comment->user->toArray(), ['avatar_url' => $comment->user->avatar_url]),
            'replies'   => ($comment->relationLoaded('replies'))
                ? $comment->replies->map(fn($r) => [
                    'id'         => $r->id,
                    'content'    => $r->content,
                    'parent_id'  => $r->parent_id,
                    'pinned_at'  => $r->pinned_at,
                    'hidden_at'  => $r->hidden_at,
                    'created_at' => $r->created_at,
                    'user'       => array_merge($r->user->toArray(), ['avatar_url' => $r->user->avatar_url]),
                    'replies'    => [],
                  ])->toArray()
                : [],
        ];
    }
}