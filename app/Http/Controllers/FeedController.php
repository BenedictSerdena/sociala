<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Story;
use Inertia\Inertia;

class FeedController extends Controller
{
    public function index()
    {
        $followingIds = auth()->user()->following()->pluck('following_id');

        $posts = Post::with('user')
            ->whereIn('user_id', $followingIds->push(auth()->id()))
            ->latest()
            ->paginate(15);

        $suggestions = \App\Models\User::whereNotIn('id', $followingIds->push(auth()->id()))
            ->withCount('followers')
            ->orderByDesc('followers_count')
            ->limit(5)
            ->get()
            ->map(fn($u) => array_merge($u->toArray(), ['avatar_url' => $u->avatar_url]));

        $storyUserIds = $followingIds->push(auth()->id())->unique();
        $stories = Story::active()
            ->with('user')
            ->whereIn('user_id', $storyUserIds)
            ->latest()
            ->get()
            ->groupBy('user_id')
            ->map(fn($group) => $group->first())
            ->values()
            ->map(fn($s) => [
                'id' => $s->id,
                'image_url' => $s->image_url,
                'created_at' => $s->created_at,
                'expires_at' => $s->expires_at,
                'user' => array_merge($s->user->toArray(), ['avatar_url' => $s->user->avatar_url]),
            ]);

        return Inertia::render('Feed/Index', [
            'posts' => $posts,
            'suggestions' => $suggestions,
            'stories' => $stories,
        ]);
    }
}
