<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the channels that your application
| supports casting events on. Returning true authorizes the user for
| the channel.
|
*/

/**
 * Authorize private user channels.
 * 
 * Users can only listen to their own private channel.
 * Format: private-user.{userId}
 */
Broadcast::channel('user.{id}', function ($user, $id) {
    return (string) $user->id === (string) $id;
});

/**
 * Authorize private session channels.
 * 
 * Only the session host and admitted members can listen to session channels.
 * Format: private-session.{sessionId}
 */
Broadcast::channel('session.{sessionId}', function ($user, $sessionId) {
    $session = \App\Models\PrayerSession::find($sessionId);

    if (!$session) {
        return false;
    }

    // Host can access
    if ((string) $user->id === (string) $session->host_id) {
        return true;
    }

    // Admitted members can access
    return $session->members()
        ->where('user_id', $user->id)
        ->where('status', 'admitted')
        ->exists();
});
