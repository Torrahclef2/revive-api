<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @group User
 *
 * User profile, progress stats, and account settings.
 */
class UserController extends Controller
{
    /**
     * My Progress
     *
     * Return the authenticated user's profile and activity statistics.
     *
     * @response 200 scenario="Success" {"user":{"id":1,"name":"John","username":"johndoe","avatar":null,"headline":"Here to pray","level":"Disciple","streak":3,"is_verified":false,"messaging_privacy":"everyone"},"stats":{"sessions_joined":10,"sessions_hosted":4}}
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
     * Update Settings
     *
     * Update profile fields and privacy settings for the authenticated user.
     * All fields are optional — only send what needs to change.
     *
     * @bodyParam name string optional Display name. Example: John Doe
     * @bodyParam username string optional Unique username (alphanumeric/dashes). Example: johndoe
     * @bodyParam headline string optional Short bio, max 160 characters. Example: Walking in faith daily.
     * @bodyParam avatar string optional URL to the user's avatar image. Example: https://cdn.example.com/avatar.jpg
     * @bodyParam messaging_privacy string optional Who can send DMs. Enum: `everyone`, `verified_only`, `disabled`. Example: verified_only
     * @response 200 scenario="Updated" {"message":"Settings updated successfully.","user":{"id":1,"messaging_privacy":"verified_only"}}
     * @response 422 scenario="Validation error" {"message":"The username has already been taken."}
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
