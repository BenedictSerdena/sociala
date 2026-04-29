<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PostController extends Controller
{
    public function show(Post $post)
    {
        $post->load('user', 'comments.user', 'likes');
        return Inertia::render('Posts/Show', ['post' => $post]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
            'image' => 'nullable|image|max:5120',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('posts', config('filesystems.default'));
        }

        $post = auth()->user()->posts()->create([
            'content' => $validated['content'],
            'image' => $imagePath,
        ]);

        $post->load('user');

        return back()->with('success', 'Post created!');
    }

    public function update(Request $request, Post $post)
    {
        abort_if($post->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'content' => 'required|string|max:2000',
            'visibility' => 'sometimes|in:public,private,archived',
        ]);

        $post->update($validated);

        return response()->json([
            'content' => $post->content,
            'visibility' => $post->visibility,
        ]);
    }

    public function setVisibility(Request $request, Post $post)
    {
        abort_if($post->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'visibility' => 'required|in:public,private,archived',
        ]);

        $post->update(['visibility' => $validated['visibility']]);

        return response()->json(['visibility' => $post->visibility]);
    }

    public function destroy(Post $post)
    {
        abort_if($post->user_id !== auth()->id(), 403);

        if ($post->image) {
            Storage::delete($post->image);
        }

        $post->delete();

        return back()->with('success', 'Post deleted.');
    }
}
