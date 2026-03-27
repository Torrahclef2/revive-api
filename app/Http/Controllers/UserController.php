<?php

namespace App\Http\Controllers;

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
                'id'     => $user->id,
                'name'   => $user->name,
                'avatar' => $user->avatar,
                'level'  => $user->level,
                'streak' => $user->streak,
            ],
            'stats' => [
                'sessions_joined' => $sessionsJoined,
                'sessions_hosted' => $sessionsHosted,
            ],
        ]);
    }
}
