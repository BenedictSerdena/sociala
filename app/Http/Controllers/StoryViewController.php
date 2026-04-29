<?php

namespace App\Http\Controllers;

use App\Models\Story;

class StoryViewController extends Controller
{
    public function store(Story $story)
    {
        if ($story->user_id === auth()->id()) {
            return response()->json(['views_count' => $story->views()->count()]);
        }

        $story->views()->firstOrCreate(['user_id' => auth()->id()]);

        return response()->json(['views_count' => $story->views()->count()]);
    }

    public function index(Story $story)
    {
        abort_if($story->user_id !== auth()->id(), 403);

        $viewers = $story->views()
            ->with('user')
            ->latest()
            ->get()
            ->map(fn($v) => [
                'id' => $v->user->id,
                'name' => $v->user->name,
                'username' => $v->user->username,
                'avatar_url' => $v->user->avatar_url,
                'viewed_at' => $v->created_at,
            ]);

        return response()->json([
            'views_count' => $viewers->count(),
            'viewers' => $viewers,
        ]);
    }
}
