<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBanned
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();

            // Legacy permanent ban
            if ($user->banned_at !== null) {
                return response()->json([
                    'message' => 'Your account has been suspended.',
                    'reason'  => $user->ban_reason,
                ], 403);
            }

            // New is_banned flag (permanent)
            if ($user->is_banned) {
                return response()->json(['message' => 'Your account has been banned.'], 403);
            }

            // Temporary ban still in effect
            if ($user->banned_until !== null && $user->banned_until->isFuture()) {
                return response()->json([
                    'message'     => 'Your account is temporarily suspended.',
                    'banned_until' => $user->banned_until->toIso8601String(),
                ], 403);
            }
        }

        return $next($request);
    }
}
