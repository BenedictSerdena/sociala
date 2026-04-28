<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->get('q', '');

        $users = User::when($query, fn($q) => $q->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('username', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            }))
            ->withCount(['posts', 'followers'])
            ->latest()
            ->paginate(25)
            ->through(fn($u) => [
                'id'              => $u->id,
                'name'            => $u->name,
                'username'        => $u->username,
                'email'           => $u->email,
                'avatar_url'      => $u->avatar_url,
                'is_admin'        => $u->is_admin,
                'banned_at'       => $u->banned_at,
                'posts_count'     => $u->posts_count,
                'followers_count' => $u->followers_count,
                'created_at'      => $u->created_at,
            ]);

        return Inertia::render('Admin/Users/Index', ['users' => $users, 'query' => $query]);
    }

    public function show(User $user)
    {
        $posts = $user->posts()->withCount(['likes', 'comments'])->latest()->paginate(10)->through(fn($p) => [
            'id'             => $p->id,
            'content'        => $p->content,
            'visibility'     => $p->visibility,
            'likes_count'    => $p->likes_count,
            'comments_count' => $p->comments_count,
            'created_at'     => $p->created_at,
        ]);

        $reports = \App\Models\CommentReport::with('comment')
            ->whereHas('comment', fn($q) => $q->where('user_id', $user->id))
            ->latest()->limit(10)->get()->map(fn($r) => [
                'id'          => $r->id,
                'reason'      => $r->reason,
                'resolved_at' => $r->resolved_at,
                'created_at'  => $r->created_at,
                'comment'     => ['id' => $r->comment->id, 'content' => $r->comment->content],
            ]);

        $auditLogs = AdminAuditLog::with('admin')
            ->where('target_type', 'user')->where('target_id', $user->id)
            ->latest()->limit(20)->get()->map(fn($l) => [
                'id'         => $l->id,
                'action'     => $l->action,
                'meta'       => $l->meta,
                'created_at' => $l->created_at,
                'admin'      => ['name' => $l->admin->name, 'username' => $l->admin->username],
            ]);

        return Inertia::render('Admin/Users/Show', [
            'profileUser' => array_merge($user->toArray(), [
                'avatar_url'      => $user->avatar_url,
                'followers_count' => $user->followers()->count(),
                'following_count' => $user->following()->count(),
                'posts_count'     => $user->posts()->count(),
            ]),
            'posts'     => $posts,
            'reports'   => $reports,
            'auditLogs' => $auditLogs,
        ]);
    }

    public function ban(User $user)
    {
        abort_if($user->is_admin, 403, 'Cannot ban an admin.');
        $wasBanned = (bool) $user->banned_at;
        $user->update(['banned_at' => $wasBanned ? null : now()]);
        AdminAuditLog::record($wasBanned ? 'user.unbanned' : 'user.banned', 'user', $user->id, ['username' => $user->username]);
        return back()->with('success', $wasBanned ? 'User unbanned.' : 'User banned.');
    }

    public function promote(User $user)
    {
        $wasAdmin = $user->is_admin;
        $user->update(['is_admin' => !$wasAdmin]);
        AdminAuditLog::record($wasAdmin ? 'user.demoted' : 'user.promoted', 'user', $user->id, ['username' => $user->username]);
        return back()->with('success', $wasAdmin ? 'Admin role removed.' : 'User promoted to admin.');
    }

    public function destroy(User $user)
    {
        abort_if($user->id === auth()->id(), 403, 'Cannot delete yourself.');
        AdminAuditLog::record('user.deleted', 'user', $user->id, ['username' => $user->username, 'email' => $user->email]);
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'ids'    => 'required|array',
            'ids.*'  => 'integer',
            'action' => 'required|in:ban,unban,delete',
        ]);

        $ids = array_filter($request->ids, fn($id) => $id !== auth()->id());
        $users = User::whereIn('id', $ids)->where('is_admin', false)->get();

        foreach ($users as $user) {
            if ($request->action === 'ban') {
                $user->update(['banned_at' => now()]);
                AdminAuditLog::record('user.banned', 'user', $user->id, ['username' => $user->username, 'bulk' => true]);
            } elseif ($request->action === 'unban') {
                $user->update(['banned_at' => null]);
                AdminAuditLog::record('user.unbanned', 'user', $user->id, ['username' => $user->username, 'bulk' => true]);
            } elseif ($request->action === 'delete') {
                AdminAuditLog::record('user.deleted', 'user', $user->id, ['username' => $user->username, 'bulk' => true]);
                $user->delete();
            }
        }

        return back()->with('success', "Bulk {$request->action} applied to {$users->count()} users.");
    }
}
