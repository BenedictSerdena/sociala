<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users'     => User::count(),
            'total_posts'     => Post::count(),
            'total_comments'  => Comment::count(),
            'pending_reports' => CommentReport::whereNull('resolved_at')->count(),
            'new_users_week'  => User::where('created_at', '>=', now()->subWeek())->count(),
            'new_posts_week'  => Post::where('created_at', '>=', now()->subWeek())->count(),
            'banned_users'    => User::whereNotNull('banned_at')->count(),
            'active_today'    => User::where('updated_at', '>=', now()->startOfDay())->count(),
        ];

        // 30-day signups chart
        $signupsChart = User::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // 30-day posts chart
        $postsChart = Post::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $chartLabels = [];
        $signupsData = [];
        $postsData   = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $label = now()->subDays($i)->format('M j');
            $chartLabels[] = $label;
            $signupsData[] = $signupsChart[$date]->count ?? 0;
            $postsData[]   = $postsChart[$date]->count ?? 0;
        }

        $recentReports = CommentReport::with(['comment.user', 'user'])
            ->whereNull('resolved_at')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'id'       => $r->id,
                'reason'   => $r->reason,
                'created_at' => $r->created_at,
                'reporter' => ['name' => $r->user->name, 'username' => $r->user->username],
                'comment'  => ['id' => $r->comment->id, 'content' => $r->comment->content, 'user' => ['name' => $r->comment->user->name]],
            ]);

        $recentUsers = User::latest()->limit(5)->get()->map(fn($u) => [
            'id'         => $u->id,
            'name'       => $u->name,
            'username'   => $u->username,
            'avatar_url' => $u->avatar_url,
            'created_at' => $u->created_at,
            'is_admin'   => $u->is_admin,
            'banned_at'  => $u->banned_at,
        ]);

        $recentAuditLogs = AdminAuditLog::with('admin')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($l) => [
                'id'         => $l->id,
                'action'     => $l->action,
                'meta'       => $l->meta,
                'created_at' => $l->created_at,
                'admin'      => ['name' => $l->admin->name, 'username' => $l->admin->username],
            ]);

        return Inertia::render('Admin/Dashboard', [
            'stats'          => $stats,
            'chartLabels'    => $chartLabels,
            'signupsData'    => $signupsData,
            'postsData'      => $postsData,
            'recentReports'  => $recentReports,
            'recentUsers'    => $recentUsers,
            'recentAuditLogs' => $recentAuditLogs,
        ]);
    }
}
