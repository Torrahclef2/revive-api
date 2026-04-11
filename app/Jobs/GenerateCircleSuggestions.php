<?php

namespace App\Jobs;

use App\Models\Circle;
use App\Models\CircleSuggestion;
use App\Models\PrayerSession;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateCircleSuggestions implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private PrayerSession $session
    ) {}

    /**
     * Execute the job.
     *
     * Generates circle suggestions for anonymous sessions.
     * Creates CircleSuggestion records between each pair of admitted members
     * who are not already connected and don't have existing suggestions.
     */
    public function handle(): void
    {
        // Only generate suggestions for anonymous sessions
        if ($this->session->visibility !== 'anonymous') {
            Log::debug('Skipping suggestions for non-anonymous session', [
                'session_id' => $this->session->id,
                'visibility' => $this->session->visibility,
            ]);
            return;
        }

        Log::info('GenerateCircleSuggestions job started', [
            'session_id' => $this->session->id,
        ]);

        // Get all admitted members
        $admittedMembers = $this->session->members()
            ->where('status', 'admitted')
            ->select('user_id')
            ->pluck('user_id')
            ->toArray();

        $suggestionsCreated = 0;
        $suggestionsSkipped = 0;

        // Create suggestions for each pair of members
        for ($i = 0; $i < count($admittedMembers); $i++) {
            for ($j = $i + 1; $j < count($admittedMembers); $j++) {
                $userId1 = $admittedMembers[$i];
                $userId2 = $admittedMembers[$j];

                try {
                    // Check if they're already in each other's circles
                    $existingCircle = Circle::where(function ($query) use ($userId1, $userId2) {
                        $query->where(function ($q) use ($userId1, $userId2) {
                            $q->where('requester_id', $userId1)
                              ->where('receiver_id', $userId2);
                        })->orWhere(function ($q) use ($userId1, $userId2) {
                            $q->where('requester_id', $userId2)
                              ->where('receiver_id', $userId1);
                        });
                    })->where('status', 'accepted')->exists();

                    if ($existingCircle) {
                        $suggestionsSkipped++;
                        continue;
                    }

                    // Check if suggestion already exists
                    $existingSuggestion = CircleSuggestion::where(function ($query) use ($userId1, $userId2) {
                        $query->where(function ($q) use ($userId1, $userId2) {
                            $q->where('from_user_id', $userId1)
                              ->where('to_user_id', $userId2);
                        })->orWhere(function ($q) use ($userId1, $userId2) {
                            $q->where('from_user_id', $userId2)
                              ->where('to_user_id', $userId1);
                        });
                    })->exists();

                    if ($existingSuggestion) {
                        $suggestionsSkipped++;
                        continue;
                    }

                    // Create bidirectional suggestions
                    CircleSuggestion::create([
                        'session_id' => $this->session->id,
                        'from_user_id' => $userId1,
                        'to_user_id' => $userId2,
                        'status' => 'pending',
                    ]);

                    CircleSuggestion::create([
                        'session_id' => $this->session->id,
                        'from_user_id' => $userId2,
                        'to_user_id' => $userId1,
                        'status' => 'pending',
                    ]);

                    $suggestionsCreated += 2;

                    Log::debug('Circle suggestion created', [
                        'session_id' => $this->session->id,
                        'from_user' => $userId1,
                        'to_user' => $userId2,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error creating circle suggestion', [
                        'session_id' => $this->session->id,
                        'user_1' => $userId1,
                        'user_2' => $userId2,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        Log::info('GenerateCircleSuggestions job completed', [
            'session_id' => $this->session->id,
            'members_count' => count($admittedMembers),
            'suggestions_created' => $suggestionsCreated,
            'suggestions_skipped' => $suggestionsSkipped,
        ]);
    }
}
