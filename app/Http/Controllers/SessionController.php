<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateSessionRequest;
use App\Http\Requests\JoinSessionRequest;
use App\Models\Session;
use App\Models\SessionParticipant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @group Sessions
 *
 * Create and manage real-time prayer and Bible study sessions.
 */
class SessionController extends Controller
{
    /**
     * Create Session
     *
     * Create a new prayer or Bible study session. The authenticated user becomes the host.
     *
     * @bodyParam type string required Session type. Enum: `prayer`, `bible_study`. Example: prayer
     * @bodyParam max_participants integer required Max users allowed (2–10). Example: 6
     * @bodyParam duration integer required Session length in minutes (10–120). Example: 30
     * @bodyParam privacy string required Visibility setting. Enum: `public`, `anonymous`, `group`. Example: public
     * @bodyParam meta array optional Key/value metadata (e.g. prayer request or Bible topic).
     * @bodyParam meta[].key string required Meta key. Example: prayer_request
     * @bodyParam meta[].value string required Meta value. Example: Healing for my family
     * @response 201 scenario="Created" {"session":{"id":1,"type":"prayer","status":"waiting","privacy":"public","max_participants":6,"duration":30},"channel_name":"session_1"}
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
     * List Live Sessions
     *
     * Return all sessions currently in `waiting` or `live` status, with participant counts.
     *
     * @response 200 scenario="Success" {"sessions":[{"id":1,"type":"prayer","status":"waiting","privacy":"public","active_participants_count":2,"host":{"id":1,"name":"John","avatar":null}}]}
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
     * Join Session
     *
     * Join an active session. An alias is auto-generated if none is provided.
     * Returns a channel name for RTC connection.
     *
     * @urlParam id integer required The session ID. Example: 1
     * @bodyParam alias string optional Custom display name inside the session. Example: Brother_77
     * @response 200 scenario="Joined" {"alias":"Brother_77","channel_name":"session_1","rtc_token":null}
     * @response 422 scenario="Session full" {"message":"Session is full."}
     * @response 422 scenario="Already joined" {"message":"You are already in this session."}
     * @response 422 scenario="Session ended" {"message":"This session has already ended."}
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
     * Leave Session
     *
     * Mark the authenticated user as having left the session.
     *
     * @urlParam id integer required The session ID. Example: 1
     * @response 200 scenario="Left" {"message":"You have left the session."}
     * @response 422 scenario="Not a participant" {"message":"You are not an active participant in this session."}
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
     * End Session
     *
     * End a session permanently. Only the host may perform this action.
     *
     * @urlParam id integer required The session ID. Example: 1
     * @response 200 scenario="Ended" {"message":"Session ended successfully.","session":{"id":1,"status":"ended","ended_at":"2026-03-27T10:00:00Z"}}
     * @response 403 scenario="Not the host" {"message":"Only the host can end this session."}
     * @response 422 scenario="Already ended" {"message":"Session is already ended."}
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
