<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Return the authenticated user's progress and activity stats.
     */
    public function getProgress(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $sessionsJoined = $user->sessionParticipants()->count();
        $sessionsHosted = $user->hostedSessions()->count();

        return response()->json([
            'user' => [
                'id'                => $user->id,
                'name'              => $user->name,
                'username'          => $user->username,
                'avatar'            => $user->avatar,
                'headline'          => $user->headline,
                'level'             => $user->level,
                'streak'            => $user->streak,
                'is_verified'       => $user->is_verified,
                'messaging_privacy' => $user->messaging_privacy,
            ],
            'stats' => [
                'sessions_joined' => $sessionsJoined,
                'sessions_hosted' => $sessionsHosted,
            ],
        ]);
    }

    /**
     * Update the authenticated user's privacy and profile settings.
     */
    public function updateSettings(UpdateSettingsRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $user->update($request->only([
            'name',
            'username',
            'headline',
            'avatar',
            'messaging_privacy',
        ]));

        return response()->json([
            'message' => 'Settings updated successfully.',
            'user'    => $user->fresh(),
        ]);
    }
}
