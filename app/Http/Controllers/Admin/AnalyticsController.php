<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Session;
use App\Models\SessionParticipant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {
        // ── 30-day labels ──────────────────────────────────────────────
        $labels        = collect();
        $dauCounts     = collect();
        $sessionCounts = collect();
        $newUserCounts = collect();

        // DAU: distinct users who joined a session each day
        $dau = SessionParticipant::select(
                DB::raw('DATE(joined_at) as date'),
                DB::raw('COUNT(DISTINCT user_id) as count')
            )
            ->where('joined_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        // Sessions created per day
        $sessionsPerDay = Session::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        // New users per day
        $newUsers = User::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $labels->push(now()->subDays($i)->format('M d'));
            $dauCounts->push($dau->get($date, 0));
            $sessionCounts->push($sessionsPerDay->get($date, 0));
            $newUserCounts->push($newUsers->get($date, 0));
        }

        // ── Session type breakdown ─────────────────────────────────────
        $typeBreakdown = Session::select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type');

        // ── Retention rate: users with 2+ session participations ───────
        $totalActive = User::whereHas('sessionParticipants')->count();
        $retained    = User::whereHas('sessionParticipants', function ($q) {
            $q->havingRaw('COUNT(*) > 1');
        }, '>', 0)->count();

        // Simpler reliable version using a subquery
        $retained = DB::table('session_participants')
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        $retentionRate = $totalActive > 0 ? round(($retained / $totalActive) * 100, 1) : 0;

        // ── Top session hosts ──────────────────────────────────────────
        $topHosts = User::withCount('hostedSessions')
            ->orderByDesc('hosted_sessions_count')
            ->limit(10)
            ->get();

        // ── Summary stats ──────────────────────────────────────────────
        $stats = [
            'total_users'     => User::count(),
            'active_users_7d' => DB::table('session_participants')
                ->where('joined_at', '>=', now()->subDays(7))
                ->distinct('user_id')
                ->count('user_id'),
            'banned_users'    => User::whereNotNull('banned_at')->count(),
            'pending_reports' => Report::where('status', 'pending')->count(),
            'retention_rate'  => $retentionRate,
            'prayer_sessions' => Session::where('type', 'prayer')->count(),
            'study_sessions'  => Session::where('type', 'bible_study')->count(),
        ];

        return view('admin.analytics.index', compact(
            'labels', 'dauCounts', 'sessionCounts', 'newUserCounts',
            'typeBreakdown', 'retentionRate', 'topHosts', 'stats'
        ));
    }
}
