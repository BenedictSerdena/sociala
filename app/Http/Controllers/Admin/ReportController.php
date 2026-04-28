<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\CommentReport;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index()
    {
        $reports = CommentReport::with(['comment.user', 'comment.post', 'user'])
            ->latest()
            ->paginate(20)
            ->through(fn($r) => [
                'id'          => $r->id,
                'reason'      => $r->reason,
                'resolved_at' => $r->resolved_at,
                'created_at'  => $r->created_at,
                'reporter'    => ['id' => $r->user->id, 'name' => $r->user->name, 'username' => $r->user->username],
                'comment'     => [
                    'id'      => $r->comment->id,
                    'content' => $r->comment->content,
                    'user'    => ['id' => $r->comment->user->id, 'name' => $r->comment->user->name, 'username' => $r->comment->user->username],
                    'post_id' => $r->comment->post_id,
                ],
            ]);

        return Inertia::render('Admin/Reports/Index', compact('reports'));
    }

    public function dismiss(CommentReport $report)
    {
        $report->update(['resolved_at' => now()]);
        AdminAuditLog::record('report.dismissed', 'comment', $report->comment_id, ['reason' => $report->reason]);
        return back()->with('success', 'Report dismissed.');
    }

    public function deleteComment(CommentReport $report)
    {
        $comment = $report->comment;
        AdminAuditLog::record('comment.deleted_via_report', 'comment', $comment->id, [
            'content' => substr($comment->content, 0, 100),
            'reason'  => $report->reason,
        ]);
        $comment->replies()->delete();
        $comment->reports()->update(['resolved_at' => now()]);
        $comment->delete();
        return back()->with('success', 'Comment deleted and report resolved.');
    }
}
