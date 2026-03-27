<?php

use App\Models\SessionParticipant;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
| Authorization for private WebSocket channels.
| A user may listen on private-session.{id} only if they are an
| active (non-removed) participant of that session.
*/

Broadcast::channel('session.{sessionId}', function ($user, int $sessionId): bool {
    return SessionParticipant::where('session_id', $sessionId)
        ->where('user_id', $user->id)
        ->whereNull('left_at')
        ->where('is_removed', false)
        ->exists();
});
