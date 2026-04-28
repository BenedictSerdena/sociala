<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
                    ->whereNull('deleted_for_everyone_at')
                    ->whereNull('deleted_for_receiver_at')
                    ->count();

                $lastContent = null;
                if ($lastMsg) {
                    if ($lastMsg->deleted_for_everyone_at) $lastContent = 'Message deleted';
                    elseif ($lastMsg->image && !$lastMsg->content) $lastContent = '📷 Photo';
                    else $lastContent = $lastMsg->content;
                }

                return array_merge($u->toArray(), [
                    'avatar_url'      => $u->avatar_url,
                    'last_message'    => $lastContent,
                    'last_message_at' => $lastMsg ? $lastMsg->created_at : null,
                    'unread_count'    => $unread,
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
        $authId = auth()->id();

        $messages = Message::where(function ($q) use ($user, $authId) {
            $q->where('sender_id', $authId)->where('receiver_id', $user->id)
              ->whereNull('deleted_for_sender_at');
        })->orWhere(function ($q) use ($user, $authId) {
            $q->where('sender_id', $user->id)->where('receiver_id', $authId)
              ->whereNull('deleted_for_receiver_at');
        })
        ->with('sender')
        ->orderBy('created_at')
        ->get()
        ->map(fn($m) => $this->fmt($m, $authId));

        Message::where('sender_id', $user->id)
            ->where('receiver_id', $authId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $pinnedMessages = Message::where(function ($q) use ($user, $authId) {
            $q->where('sender_id', $authId)->where('receiver_id', $user->id);
        })->orWhere(function ($q) use ($user, $authId) {
            $q->where('sender_id', $user->id)->where('receiver_id', $authId);
        })
        ->whereNotNull('pinned_at')
        ->whereNull('deleted_for_everyone_at')
        ->orderBy('pinned_at')
        ->get()
        ->map(fn($m) => $this->fmt($m, $authId));

        return Inertia::render('Messages/Show', [
            'chatUser'       => array_merge($user->toArray(), ['avatar_url' => $user->avatar_url]),
            'messages'       => $messages,
            'pinnedMessages' => $pinnedMessages,
        ]);
    }

    public function store(Request $request, User $user)
    {
        $request->validate([
            'content' => 'nullable|string|max:1000',
            'image'   => 'nullable|image|max:5120',
        ]);

        abort_if(!$request->filled('content') && !$request->hasFile('image'), 422, 'Message cannot be empty.');

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('messages', 'public');
        }

        $message = Message::create([
            'sender_id'   => auth()->id(),
            'receiver_id' => $user->id,
            'content'     => $request->input('content'),
            'image'       => $imagePath,
        ]);

        $message->load('sender');
        broadcast(new MessageSent($message))->toOthers();

        return response()->json($this->fmt($message, auth()->id()));
    }

    public function markRead(User $user)
    {
        Message::where('sender_id', $user->id)
            ->where('receiver_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function deleteForMe(Message $message)
    {
        $authId = auth()->id();
        abort_if($message->sender_id !== $authId && $message->receiver_id !== $authId, 403);

        if ($message->sender_id === $authId) {
            $message->update(['deleted_for_sender_at' => now()]);
        } else {
            $message->update(['deleted_for_receiver_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    public function deleteForEveryone(Message $message)
    {
        abort_if($message->sender_id !== auth()->id(), 403, 'Only the sender can delete for everyone.');
        $message->update(['deleted_for_everyone_at' => now(), 'content' => null, 'image' => null]);
        return response()->json(['success' => true]);
    }

    public function pin(Message $message)
    {
        $authId = auth()->id();
        abort_if($message->sender_id !== $authId && $message->receiver_id !== $authId, 403);
        $message->update(['pinned_at' => $message->pinned_at ? null : now()]);
        return response()->json(['pinned' => (bool) $message->pinned_at]);
    }

    private function fmt(Message $message, int $authId): array
    {
        return [
            'id'                    => $message->id,
            'content'               => $message->deleted_for_everyone_at ? null : $message->content,
            'image_url'             => $message->deleted_for_everyone_at ? null : $message->image_url,
            'sender_id'             => $message->sender_id,
            'read_at'               => $message->read_at,
            'pinned_at'             => $message->pinned_at,
            'deleted_for_everyone'  => (bool) $message->deleted_for_everyone_at,
            'created_at'            => $message->created_at,
            'sender'                => $message->sender ? [
                'id'         => $message->sender->id,
                'name'       => $message->sender->name,
                'avatar_url' => $message->sender->avatar_url,
            ] : null,
        ];
    }
}