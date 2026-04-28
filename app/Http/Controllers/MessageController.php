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
        $authId = auth()->id();

        $conversations = User::whereIn('id', function ($query) use ($authId) {
                $query->select('sender_id')->from('messages')->where('receiver_id', $authId)
                    ->union(
                        \DB::table('messages')->select('receiver_id')->where('sender_id', $authId)
                    );
            })
            ->get()
            ->map(function ($u) use ($authId) {
                $lastMsg = Message::where(function ($q) use ($authId, $u) {
                    $q->where('sender_id', $authId)->where('receiver_id', $u->id);
                })->orWhere(function ($q) use ($authId, $u) {
                    $q->where('sender_id', $u->id)->where('receiver_id', $authId);
                })->latest()->first();

                $unread = Message::where('sender_id', $u->id)
                    ->where('receiver_id', $authId)
                    ->whereNull('read_at')
                    ->count();

                return array_merge($u->toArray(), [
                    'avatar_url' => $u->avatar_url,
                    'last_message' => $lastMsg ? $lastMsg->content : null,
                    'last_message_at' => $lastMsg ? $lastMsg->created_at : null,
                    'unread_count' => $unread,
                ]);
            })
            ->sortByDesc('last_message_at')
            ->values();

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
        })->with('sender')->orderBy('created_at')->get(['id','sender_id','receiver_id','content','read_at','created_at']);

        Message::where('sender_id', $user->id)->where('receiver_id', auth()->id())->whereNull('read_at')->update(['read_at' => now()]);

        return Inertia::render('Messages/Show', [
            'chatUser' => array_merge($user->toArray(), ['avatar_url' => $user->avatar_url]),
            'messages' => $messages,
        ]);
    }

    public function markRead(User $user)
    {
        Message::where('sender_id', $user->id)
            ->where('receiver_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
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
