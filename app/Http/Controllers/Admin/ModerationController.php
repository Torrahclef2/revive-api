<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;

class ModerationController extends Controller
{
    public function index(Request $request)
    {
        $statusFilter = $request->get('status', 'pending');

        $reports = Report::with(['reporter', 'reportedUser', 'reportedSession', 'reviewer'])
            ->when($statusFilter !== 'all', fn ($q) => $q->where('status', $statusFilter))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $pendingCount = Report::where('status', 'pending')->count();

        $bannedUsers = User::whereNotNull('banned_at')
            ->orderByDesc('banned_at')
            ->get();

        return view('admin.moderation.index', compact('reports', 'bannedUsers', 'statusFilter', 'pendingCount'));
    }

    public function banUser(Request $request, User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot ban yourself.');
        }

        $request->validate(['reason' => 'required|string|max:255']);

        $user->update([
            'banned_at'  => now(),
            'ban_reason' => $request->reason,
        ]);

        return back()->with('success', "{$user->name} has been banned.");
    }

    public function unbanUser(User $user)
    {
        $user->update(['banned_at' => null, 'ban_reason' => null]);
        return back()->with('success', "{$user->name} has been unbanned.");
    }

    public function reviewReport(Report $report)
    {
        $report->update([
            'status'      => 'reviewed',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Report marked as reviewed.');
    }

    public function dismissReport(Report $report)
    {
        $report->update([
            'status'      => 'dismissed',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Report dismissed.');
    }
}
