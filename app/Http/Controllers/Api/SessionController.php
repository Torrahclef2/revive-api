<?php

namespace App\Http\Controllers\Api;

use App\Events\SessionEnded;
use App\Events\SessionWentLive;
use App\Http\Controllers\ApiController;
use App\Http\Requests\CreateSessionRequest;
use App\Http\Resources\AnonymousSessionResource;
use App\Http\Resources\SessionResource;
use App\Models\PrayerSession;
use App\Models\SessionMember;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SessionController extends ApiController
{
    /**
     * Get discoverable prayer sessions feed.
     * 
     * Returns open and anonymous sessions filtered by location and gender.
     * Excludes full sessions and ended sessions.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function discovery(Request $request)
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 15);
        $country = $request->get('location_country', $user->location_country);

        // Build discoverable sessions query
        $sessions = PrayerSession::query()
            // Status: not ended
            ->where('status', '!=', 'ended')
            // Visibility: open or anonymous
            ->whereIn('visibility', ['open', 'anonymous'])
            // Location: match user's country
            ->where('location_country', $country)
            // Gender: any or match user's gender
            ->byGender($user->gender)
            // Not full: exclude sessions at capacity
            ->notFull()
            // Load host for open sessions
            ->with('host')
            ->orderByDesc('scheduled_at')
            ->paginate($perPage);

        // Transform to appropriate resource based on visibility
        $data = $sessions->map(function ($session) {
            return $session->visibility === 'anonymous'
                ? new AnonymousSessionResource($session)
                : new SessionResource($session);
        });

        return $this->paginated(
            $data,
            'Discoverable sessions retrieved',
            $sessions
        );
    }

    /**
     * Create a new prayer session.
     * 
     * @param CreateSessionRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateSessionRequest $request)
    {
        $user = $request->user();

        // Generate unique Agora channel name
        $agoraChannelName = PrayerSession::generateAgoraChannelName();

        // Create the session
        $session = PrayerSession::create([
            'host_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'purpose' => $request->purpose,
            'template' => $request->template,
            'visibility' => $request->visibility,
            'status' => 'upcoming',
            'gender_preference' => $request->gender_preference,
            'location_city' => $request->location_city,
            'location_country' => $request->location_country,
            'max_members' => $request->max_members,
            'scheduled_at' => $request->scheduled_at,
            'duration_minutes' => $request->duration_minutes,
            'agora_channel_name' => $agoraChannelName,
        ]);

        // Auto-admit the host as a member
        SessionMember::create([
            'session_id' => $session->id,
            'user_id' => $user->id,
            'status' => 'admitted',
            'joined_at' => now(),
        ]);

        $session->load('host');

        return $this->created(
            new SessionResource($session),
            'Prayer session created successfully'
        );
    }

    /**
     * Get session details.
     * 
     * @param PrayerSession $session
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(PrayerSession $session, Request $request)
    {
        $user = $request->user();

        // If session is ended and user is not host, return 404
        if ($session->status === 'ended' && $session->host_id !== $user->id) {
            return $this->notFound('Session not found or has ended');
        }

        // Load relations
        $session->load('host', 'members');

        // Choose resource based on visibility
        $resource = $session->visibility === 'anonymous'
            ? new AnonymousSessionResource($session)
            : new SessionResource($session);

        return $this->success($resource, 'Session retrieved');
    }

    /**
     * Host starts the prayer session (makes it live).
     * 
     * @param PrayerSession $session
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function goLive(PrayerSession $session, Request $request)
    {
        $user = $request->user();

        // Authorization: only host can start session
        if ($session->host_id !== $user->id) {
            return $this->forbidden('Only the host can start this session');
        }

        // Validation: session must be upcoming or admitting
        if (!in_array($session->status, ['upcoming', 'admitting'])) {
            return $this->conflict('Session cannot be started in its current status');
        }

        // Update session status
        $session->update([
            'status' => 'live',
            'live_started_at' => now(),
        ]);

        // Fire event to notify all admitted members
        SessionWentLive::dispatch($session);

        $session->load('host');

        return $this->success(
            new SessionResource($session),
            'Session is now live'
        );
    }

    /**
     * Host ends the prayer session.
     * 
     * Triggers:
     * - XP awards to host and admitted members
     * - Circle suggestions for anonymous session members
     * - Post-session thread creation
     *
     * @param PrayerSession $session
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function end(PrayerSession $session, Request $request)
    {
        $user = $request->user();

        // Authorization: only host can end session
        if ($session->host_id !== $user->id) {
            return $this->forbidden('Only the host can end this session');
        }

        // Validation: session must be live
        if ($session->status !== 'live') {
            return $this->conflict('Only live sessions can be ended');
        }

        // Update session status
        $session->update([
            'status' => 'ended',
            'live_ended_at' => now(),
        ]);

        // Fire SessionEnded event
        // This will trigger listeners for:
        // - XP awards (StreakService)
        // - Circle suggestions generation
        // - Post-session thread creation
        SessionEnded::dispatch($session);

        $session->load('host');

        return $this->success(
            new SessionResource($session),
            'Session has ended'
        );
    }
}
