<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateSessionRequest;
use App\Http\Requests\JoinSessionRequest;
use App\Models\Session;
use App\Models\SessionParticipant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    /**
     * Create a new session.
     * Sets status to "waiting" and records the host as the first participant.
     * Accepts an optional meta array for prayer requests or bible topics.
     */
    public function createSession(CreateSessionRequest $request): JsonResponse
    {
        $session = Session::create([
            'type'             => $request->type,
            'host_id'          => Auth::id(),
            'max_participants' => $request->max_participants,
            'duration'         => $request->duration,
            'privacy'          => $request->privacy,
            'status'           => 'waiting',
        ]);

        // Persist any supplementary meta data (prayer_request, bible_topic, etc.)
        // Uses a single bulk insert instead of one query per item.
        if ($request->filled('meta')) {
            $session->meta()->insert(
                collect($request->meta)->map(fn ($item) => [
                    'session_id' => $session->id,
                    'key'        => $item['key'],
                    'value'      => $item['value'],
                ])->all()
            );
        }

        // Register the creator immediately as the host participant
        SessionParticipant::create([
            'session_id' => $session->id,
            'user_id'    => Auth::id(),
            'alias'      => Auth::user()->name ?? 'Host',
            'role'       => 'host',
            'joined_at'  => now(),
        ]);

        return response()->json([
            'session'      => $session->load('meta'),
            'channel_name' => 'session_' . $session->id,
        ], 201);
    }

    /**
     * Return all sessions that are currently waiting or live.
     */
    public function getLiveSessions(): JsonResponse
    {
        $sessions = Session::whereIn('status', ['waiting', 'live'])
            ->with(['host:id,name,avatar'])
            ->withCount('activeParticipants')
            ->get();

        return response()->json(['sessions' => $sessions]);
    }

    /**
     * Join a session.
     *
     * - Prevents joining ended or full sessions.
     * - Generates an anonymous alias (e.g. "Brother_4829") if none is provided
     *   and the user has no display name set.
     * - Returns the alias, channel name, and a placeholder RTC token.
     */
    public function joinSession(JoinSessionRequest $request, int $id): JsonResponse
    {
        $session = Session::findOrFail($id);

        if ($session->status === 'ended') {
            return response()->json(['message' => 'This session has already ended.'], 422);
        }

        // Single query: get total active count and whether the current user is already in.
        $stats = $session->activeParticipants()
            ->selectRaw('COUNT(*) as total, SUM(user_id = ?) as is_me', [Auth::id()])
            ->first();

        if ($stats->total >= $session->max_participants) {
            return response()->json(['message' => 'Session is full.'], 422);
        }

        if ((int) $stats->is_me > 0) {
            return response()->json(['message' => 'You are already in this session.'], 422);
        }

        $alias = $this->resolveAlias($request->alias);

        $participant = SessionParticipant::create([
            'session_id' => $session->id,
            'user_id'    => Auth::id(),
            'alias'      => $alias,
            'role'       => 'participant',
            'joined_at'  => now(),
        ]);

        return response()->json([
            'alias'        => $alias,
            'channel_name' => 'session_' . $session->id,
            'rtc_token'    => null, // TODO: generate via Agora / Twilio RTC
            'participant'  => $participant,
        ]);
    }

    /**
     * Mark the authenticated user as having left the session.
     */
    public function leaveSession(int $id): JsonResponse
    {
        $session     = Session::findOrFail($id);
        $participant = $session->activeParticipants()
            ->where('user_id', Auth::id())
            ->first();

        if (! $participant) {
            return response()->json(['message' => 'You are not an active participant in this session.'], 422);
        }

        $participant->update(['left_at' => now()]);

        return response()->json(['message' => 'You have left the session.']);
    }

    /**
     * End a session. Only the host may perform this action.
     */
    public function endSession(int $id): JsonResponse
    {
        $session = Session::findOrFail($id);

        if ($session->host_id !== Auth::id()) {
            return response()->json(['message' => 'Only the host can end this session.'], 403);
        }

        if ($session->status === 'ended') {
            return response()->json(['message' => 'Session is already ended.'], 422);
        }

        $session->update([
            'status'   => 'ended',
            'ended_at' => now(),
        ]);

        return response()->json([
            'message' => 'Session ended successfully.',
            'session' => $session,
        ]);
    }

    /**
     * Resolve the display alias for a participant:
     *  1. Use the explicitly provided alias (custom/anonymous join).
     *  2. Fall back to the authenticated user's name.
     *  3. Fall back to a randomly generated anonymous alias.
     */
    private function resolveAlias(?string $provided): string
    {
        if ($provided) {
            return $provided;
        }

        $user = Auth::user();
        if ($user && $user->name) {
            return $user->name;
        }

        // Generate a random anonymous alias for fully anonymous participants
        $prefixes = ['Brother', 'Sister', 'Pilgrim', 'Seeker'];

        return $prefixes[array_rand($prefixes)] . '_' . rand(100, 9999);
    }
}
