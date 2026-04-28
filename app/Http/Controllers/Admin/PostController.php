<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->get('q', '');

        $posts = Post::with('user')
            ->when($query, fn($q) => $q->where('content', 'like', "%{$query}%"))
            ->withCount(['comments', 'likes'])
            ->latest()
            ->paginate(25)
            ->through(fn($p) => [
                'id'             => $p->id,
                'content'        => $p->content,
                'image_url'      => $p->image_url ?? null,
                'visibility'     => $p->visibility,
                'comments_count' => $p->comments_count,
                'likes_count'    => $p->likes_count,
                'created_at'     => $p->created_at,
                'user'           => ['id' => $p->user->id, 'name' => $p->user->name, 'username' => $p->user->username, 'avatar_url' => $p->user->avatar_url],
            ]);

        return Inertia::render('Admin/Posts/Index', ['posts' => $posts, 'query' => $query]);
    }

    public function destroy(Post $post)
    {
        AdminAuditLog::record('post.deleted', 'post', $post->id, [
            'content'  => substr($post->content, 0, 100),
            'username' => $post->user->username,
        ]);
        $post->delete();
        return back()->with('success', 'Post deleted.');
    }
}
