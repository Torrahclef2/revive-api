<?php

namespace App\Http\Controllers;

use App\Http\Requests\InSessionReportRequest;
use App\Http\Requests\KickParticipantRequest;
use App\Http\Requests\MuteParticipantRequest;
use App\Models\Session;
use App\Services\SessionModerationService;
use Illuminate\Http\JsonResponse;

class SessionModerationController extends Controller
{
    public function __construct(private SessionModerationService $service) {}

    public function mute(MuteParticipantRequest $request, Session $session): JsonResponse
    {
        $this->authorize('mute', $session);

        $participant = $this->service->muteUser(
            $session,
            $request->validated('user_id'),
            $request->user()->id,
        );

        return response()->json(['message' => 'User muted.', 'participant' => $participant]);
    }

    public function kick(KickParticipantRequest $request, Session $session): JsonResponse
    {
        $this->authorize('kick', $session);

        $participant = $this->service->kickUser(
            $session,
            $request->validated('user_id'),
            $request->user()->id,
        );

        return response()->json(['message' => 'User kicked.', 'participant' => $participant]);
    }

    public function end(Session $session): JsonResponse
    {
        $this->authorize('end', $session);

        $ended = $this->service->endSession($session, request()->user()->id);

        return response()->json(['message' => 'Session ended.', 'session' => $ended]);
    }

    public function report(InSessionReportRequest $request, Session $session): JsonResponse
    {
        $data = $request->validated();

        $report = $this->service->reportUser(
            $session,
            $request->user()->id,
            $data['user_id'],
            $data['reason'],
            $data['description'] ?? null,
        );

        return response()->json(['message' => 'Report submitted.', 'report' => $report], 201);
    }
}

