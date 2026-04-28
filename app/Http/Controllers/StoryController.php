<?php

namespace App\Http\Controllers;

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

        $path = $request->file('image')->store('stories', 'public');

        auth()->user()->stories()->create([
            'image' => $path,
            'expires_at' => now()->addHours(24),
        ]);

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
        Storage::disk('public')->delete($story->image);
        $story->delete();
        return back();
    }
}
