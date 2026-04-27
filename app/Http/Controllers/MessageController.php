<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MessageController extends Controller
{
    public function index()
    {
        $conversations = User::whereIn('id', function ($query) {
                $query->select('sender_id')->from('messages')->where('receiver_id', auth()->id())
                    ->union(
                        \DB::table('messages')->select('receiver_id')->where('sender_id', auth()->id())
                    );
            })
            ->get()
            ->map(fn($u) => array_merge($u->toArray(), ['avatar_url' => $u->avatar_url]));

        return Inertia::render('Messages/Index', [
            'conversations' => $conversations,
        ]);
    }

    public function show(User $user)
    {
        $messages = Message::where(function ($q) use ($user) {
            $q->where('sender_id', auth()->id())->where('receiver_id', $user->id);
        })->orWhere(function ($q) use ($user) {
            $q->where('sender_id', $user->id)->where('receiver_id', auth()->id());
        })->with('sender')->orderBy('created_at')->get();

        Message::where('sender_id', $user->id)->where('receiver_id', auth()->id())->whereNull('read_at')->update(['read_at' => now()]);

        return Inertia::render('Messages/Show', [
            'chatUser' => array_merge($user->toArray(), ['avatar_url' => $user->avatar_url]),
            'messages' => $messages,
        ]);
    }

    public function store(Request $request, User $user)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $message = Message::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $user->id,
            'content' => $validated['content'],
        ]);

        $message->load('sender');

        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message);
    }
}
