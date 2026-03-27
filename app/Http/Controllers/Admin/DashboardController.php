<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Message;
use App\Models\Session;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users'       => User::count(),
            'verified_users'    => User::where('is_verified', true)->count(),
            'total_sessions'    => Session::count(),
            'live_sessions'     => Session::where('status', 'active')->count(),
            'scheduled_sessions'=> Session::where('status', 'waiting')->whereNotNull('scheduled_at')->count(),
            'total_groups'      => Group::count(),
            'total_messages'    => Message::count(),
        ];

        // New users per day for the last 7 days
        $userGrowth = User::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        // Sessions per day for the last 7 days
        $sessionActivity = Session::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        // Fill in missing days with 0
        $labels = collect();
        $userCounts = collect();
        $sessionCounts = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $labels->push(now()->subDays($i)->format('M d'));
            $userCounts->push($userGrowth->get($date, 0));
            $sessionCounts->push($sessionActivity->get($date, 0));
        }

        $recentUsers    = User::latest()->limit(5)->get();
        $recentSessions = Session::with('host')->latest()->limit(5)->get();

        return view('admin.dashboard', compact(
            'stats', 'labels', 'userCounts', 'sessionCounts',
            'recentUsers', 'recentSessions'
        ));
    }
}
