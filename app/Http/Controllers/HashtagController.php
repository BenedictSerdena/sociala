<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Inertia\Inertia;

class HashtagController extends Controller
{
    public function show(string $tag)
    {
        $posts = Post::with('user', 'likes', 'comments.user')
            ->where('content', 'like', "%#{$tag}%")
            ->latest()
            ->paginate(10);

        return Inertia::render('Hashtags/Show', [
            'tag'   => $tag,
            'posts' => $posts,
        ]);
    }
}
