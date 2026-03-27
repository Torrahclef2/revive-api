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
        if (auth()->check() && auth()->user()->banned_at !== null) {
            return response()->json([
                'message' => 'Your account has been suspended.',
                'reason'  => auth()->user()->ban_reason,
            ], 403);
        }

        return $next($request);
    }
}
