<?php

namespace App\Http\Controllers;

use App\Events\NewNotification;
use App\Models\Notification;
use App\Models\User;

class FollowController extends Controller
{
    public function toggle(User $user)
    {
        abort_if($user->id === auth()->id(), 400);

        $existing = auth()->user()->following()->where('following_id', $user->id)->first();

        if ($existing) {
            $existing->delete();
            $following = false;
        } else {
            auth()->user()->following()->create(['following_id' => $user->id]);
            $following = true;

            $notification = Notification::create([
                'user_id' => $user->id,
                'type' => 'follow',
                'data' => [
                    'message' => auth()->user()->name . ' started following you',
                    'user_name' => auth()->user()->name,
                    'user_avatar' => auth()->user()->avatar_url,
                    'user_username' => auth()->user()->username,
                ],
            ]);
            broadcast(new NewNotification($notification))->toOthers();
        }

        return back();
    }
}
