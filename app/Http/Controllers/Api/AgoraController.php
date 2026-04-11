<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\PrayerSession;
use App\Services\AgoraService;
use Illuminate\Http\Request;

class AgoraController extends ApiController
{
    private AgoraService $agoraService;

    public function __construct(AgoraService $agoraService)
    {
        $this->agoraService = $agoraService;
    }

    /**
     * Generate an Agora RTC token for joining a prayer session.
     *
     * User must be the host or an admitted member of the session.
     *
     * @param PrayerSession $session
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateToken(PrayerSession $session, Request $request)
    {
        $user = $request->user();

        // Check if user is host
        $isHost = $session->host_id === $user->id;

        // Check if user is an admitted member
        $isAdmittedMember = $session->members()
            ->where('user_id', $user->id)
            ->where('status', 'admitted')
            ->exists();

        if (!$isHost && !$isAdmittedMember) {
            return $this->forbidden('You are not authorized to join this session');
        }

        // Validate that session has an agora channel name
        if (!$session->agora_channel_name) {
            return $this->error(
                'Session is not configured for live audio. Please start the session first.',
                400
            );
        }

        try {
            // Determine role based on user status
            $role = $isHost ? 'publisher' : 'subscriber';

            // Generate token with UID 0 for dynamic assignment
            $token = $this->agoraService->generateToken(
                $session->agora_channel_name,
                0,
                $role
            );

            return $this->success([
                'token' => $token,
                'channel_name' => $session->agora_channel_name,
                'uid' => 0,
                'app_id' => config('services.agora.app_id'),
                'role' => $role,
                'expires_in' => 3600, // seconds
            ], 'Agora token generated successfully');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to generate Agora token', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('Failed to generate Agora token');
        }
    }
}
