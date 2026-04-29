<?php

namespace App\Http\Controllers;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function show(User $user): Response
    {
        $isOwnProfile = auth()->check() && auth()->id() === $user->id;

        $postsQuery = $user->posts()->with('user')->latest();

        if ($isOwnProfile) {
            $postsQuery->whereIn('visibility', ['public', 'private']);
        } else {
            $postsQuery->where('visibility', 'public');
        }

        $posts = $postsQuery->paginate(12);

        return Inertia::render('Profile/Show', [
            'profileUser' => array_merge($user->toArray(), [
                'avatar_url' => $user->avatar_url,
                'cover_photo_url' => $user->cover_photo_url,
                'followers_count' => $user->followers()->count(),
                'following_count' => $user->following()->count(),
                'posts_count' => $user->posts()->count(),
                'is_following' => auth()->check() ? auth()->user()->isFollowing($user) : false,
            ]),
            'posts' => $posts,
        ]);
    }

    public function archived(User $user)
    {
        abort_if(auth()->id() !== $user->id, 403);

        $posts = $user->posts()
            ->with('user')
            ->where('visibility', 'archived')
            ->latest()
            ->paginate(12);

        return response()->json($posts);
    }

    public function archivedStories(User $user)
    {
        abort_if(auth()->id() !== $user->id, 403);

        $stories = $user->stories()
            ->with('user')
            ->whereNotNull('archived_at')
            ->latest()
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'image_url' => $s->image_url,
                'created_at' => $s->created_at,
                'expires_at' => $s->expires_at,
                'archived_at' => $s->archived_at,
                'user' => array_merge($s->user->toArray(), ['avatar_url' => $s->user->avatar_url]),
            ]);

        return response()->json($stories);
    }

    public function followers(User $user)
    {
        $followerIds = Follow::where('following_id', $user->id)->pluck('follower_id');
        return response()->json(
            User::whereIn('id', $followerIds)->get()
                ->map(fn($u) => array_merge($u->toArray(), ['avatar_url' => $u->avatar_url]))
        );
    }

    public function following(User $user)
    {
        $followingIds = Follow::where('follower_id', $user->id)->pluck('following_id');
        return response()->json(
            User::whereIn('id', $followingIds)->get()
                ->map(fn($u) => array_merge($u->toArray(), ['avatar_url' => $u->avatar_url]))
        );
    }

    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'user' => array_merge(auth()->user()->toArray(), [
                'avatar_url' => auth()->user()->avatar_url,
                'cover_photo_url' => auth()->user()->cover_photo_url,
            ]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|unique:users,username,' . auth()->id(),
            'bio' => 'nullable|string|max:300',
            'avatar' => 'nullable|image|max:2048',
            'cover_photo' => 'nullable|image|max:5120',
        ]);

        $user = $request->user();

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $validated['avatar'] = 'data:' . $file->getMimeType() . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));
        }

        if ($request->hasFile('cover_photo')) {
            $file = $request->file('cover_photo');
            $validated['cover_photo'] = 'data:' . $file->getMimeType() . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));
        }

        $user->update($validated);

        return back()->with('success', 'Profile updated!');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $user = $request->user();
        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
