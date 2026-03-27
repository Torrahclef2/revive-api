<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Session;
use Illuminate\Http\Request;

class SessionAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Session::with('host');

        if ($search = $request->get('search')) {
            $query->whereHas('host', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        if ($filter = $request->get('filter')) {
            match ($filter) {
                'active'    => $query->where('status', 'active'),
                'waiting'   => $query->where('status', 'waiting'),
                'ended'     => $query->where('status', 'ended'),
                'scheduled' => $query->where('status', 'waiting')->whereNotNull('scheduled_at'),
                default     => null,
            };
        }

        $sessions = $query->latest()->paginate(20)->withQueryString();

        return view('admin.sessions.index', compact('sessions'));
    }

    public function show(Session $session)
    {
        $session->load(['host', 'participants.user', 'meta']);
        return view('admin.sessions.show', compact('session'));
    }

    public function destroy(Session $session)
    {
        $session->delete();
        return redirect()->route('admin.sessions.index')->with('success', 'Session deleted.');
    }

    public function monitor()
    {
        $liveSessions = Session::with(['host', 'activeParticipants.user'])
            ->where('status', 'active')
            ->orderByDesc('started_at')
            ->get();

        $scheduledSessions = Session::with('host')
            ->where('status', 'waiting')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at')
            ->get();

        return view('admin.sessions.monitor', compact('liveSessions', 'scheduledSessions'));
    }
}
