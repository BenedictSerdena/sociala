<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = trim($request->get('q', ''));

        $users = $query
            ? User::where('id', '!=', auth()->id())
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('username', 'like', "%{$query}%");
                })
                ->withCount('followers')
                ->paginate(20)
                ->through(fn($u) => array_merge($u->toArray(), [
                    'avatar_url' => $u->avatar_url,
                    'is_following' => auth()->user()->isFollowing($u),
                ]))
            : [];

        return Inertia::render('Search/Index', [
            'users' => $users,
            'query' => $query,
        ]);
    }
}
