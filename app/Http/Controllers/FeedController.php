<?php

namespace App\Http\Controllers;

use App\Models\Post;
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

        return Inertia::render('Feed/Index', [
            'posts' => $posts,
            'suggestions' => $suggestions,
        ]);
    }
}
