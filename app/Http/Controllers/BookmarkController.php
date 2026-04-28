<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Post;
use Inertia\Inertia;

class BookmarkController extends Controller
{
    public function index()
    {
        $posts = auth()->user()
            ->bookmarks()
            ->with(['post.user', 'post.likes', 'post.comments.user'])
            ->latest()
            ->paginate(10)
            ->through(fn($b) => $b->post);

        return Inertia::render('Bookmarks/Index', ['posts' => $posts]);
    }

    public function toggle(Post $post)
    {
        $bookmark = Bookmark::where('user_id', auth()->id())
            ->where('post_id', $post->id)
            ->first();

        if ($bookmark) {
            $bookmark->delete();
            return response()->json(['bookmarked' => false]);
        }

        Bookmark::create(['user_id' => auth()->id(), 'post_id' => $post->id]);
        return response()->json(['bookmarked' => true]);
    }
}
