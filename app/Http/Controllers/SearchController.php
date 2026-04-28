<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = trim($request->get('q', ''));
        $tab   = $request->get('tab', 'users');

        $users = $posts = [];

        if ($query) {
            $users = User::where('id', '!=', auth()->id())
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('username', 'like', "%{$query}%");
                })
                ->withCount('followers')
                ->paginate(20)
                ->through(fn($u) => array_merge($u->toArray(), [
                    'avatar_url'  => $u->avatar_url,
                    'is_following' => auth()->user()->isFollowing($u),
                ]));

            $posts = Post::with('user')
                ->where('visibility', 'public')
                ->where('content', 'like', "%{$query}%")
                ->withCount(['likes', 'comments'])
                ->latest()
                ->paginate(20)
                ->through(fn($p) => [
                    'id'             => $p->id,
                    'content'        => $p->content,
                    'image_url'      => $p->image_url ?? null,
                    'likes_count'    => $p->likes_count,
                    'comments_count' => $p->comments_count,
                    'created_at'     => $p->created_at,
                    'user'           => array_merge($p->user->toArray(), ['avatar_url' => $p->user->avatar_url]),
                ]);
        }

        return Inertia::render('Search/Index', [
            'users' => $users,
            'posts' => $posts,
            'query' => $query,
            'tab'   => $tab,
        ]);
    }
}
