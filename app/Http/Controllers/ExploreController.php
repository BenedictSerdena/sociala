<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Inertia\Inertia;

class ExploreController extends Controller
{
    public function index()
    {
        $trending = Post::with('user', 'likes', 'comments.user')
            ->withCount('likes')
            ->where('created_at', '>=', now()->subDays(7))
            ->orderByDesc('likes_count')
            ->paginate(10);

        $suggested = User::where('id', '!=', auth()->id())
            ->whereNotIn('id', auth()->user()->following()->pluck('following_id'))
            ->withCount('followers')
            ->orderByDesc('followers_count')
            ->take(6)
            ->get()
            ->map(fn($u) => array_merge($u->toArray(), ['avatar_url' => $u->avatar_url]));

        return Inertia::render('Explore/Index', [
            'trending' => $trending,
            'suggested' => $suggested,
        ]);
    }
}
