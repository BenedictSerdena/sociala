<?php

namespace App\Http\Controllers;

use App\Events\StoryPosted;
use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StoryController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240',
        ]);

        $file = $request->file('image');
        $path = 'data:' . $file->getMimeType() . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));

        $story = auth()->user()->stories()->create([
            'image' => $path,
            'expires_at' => now()->addHours(24),
        ]);

        $story->load('user');

        $followerIds = auth()->user()->followers()->pluck('follower_id');
        foreach ($followerIds as $followerId) {
            broadcast(new StoryPosted($story, $followerId))->toOthers();
        }

        return back();
    }

    public function archive(Story $story)
    {
        abort_if($story->user_id !== auth()->id(), 403);
        $story->update(['archived_at' => now()]);
        return response()->json(['archived' => true]);
    }

    public function destroy(Story $story)
    {
        abort_if($story->user_id !== auth()->id(), 403);
        // base64 stored in DB, nothing to delete from disk
        $story->delete();
        return back();
    }
}
