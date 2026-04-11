<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\SearchRequest;
use App\Models\Group;
use App\Models\PrayerSession;
use App\Models\User;
use Illuminate\Http\Request;

class SearchController extends ApiController
{
    /**
     * Search for users, groups, or prayer sessions.
     *
     * Query parameters:
     * - q: search query (min 2 characters)
     * - type: search type (users, groups, or sessions)
     *
     * @param SearchRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(SearchRequest $request)
    {
        $query = $request->q;
        $type = $request->type;
        $perPage = 20;

        return match ($type) {
            'users' => $this->searchUsers($query, $perPage),
            'groups' => $this->searchGroups($query, $perPage),
            'sessions' => $this->searchSessions($query, $perPage),
        };
    }

    /**
     * Search for users by username or display name.
     *
     * @param string $query
     * @param int $perPage
     * @return \Illuminate\Http\JsonResponse
     */
    private function searchUsers(string $query, int $perPage)
    {
        $users = User::where(function ($q) use ($query) {
            $q->where('username', 'like', "%{$query}%")
              ->orWhere('display_name', 'like', "%{$query}%");
        })
        ->where('is_active', true)
        ->select(['id', 'username', 'display_name', 'avatar_url', 'headline', 'level'])
        ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Users found',
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    /**
     * Search for groups by name or description.
     *
     * @param string $query
     * @param int $perPage
     * @return \Illuminate\Http\JsonResponse
     */
    private function searchGroups(string $query, int $perPage)
    {
        $groups = Group::where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('description', 'like', "%{$query}%");
        })
        ->where('is_active', true)
        ->with([
            'creator' => function ($q) {
                $q->select(['id', 'username', 'display_name', 'avatar_url']);
            },
        ])
        ->select(['id', 'creator_id', 'name', 'description', 'purpose', 'avatar_url', 'created_at'])
        ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Groups found',
            'data' => $groups->items(),
            'meta' => [
                'current_page' => $groups->currentPage(),
                'per_page' => $groups->perPage(),
                'total' => $groups->total(),
                'last_page' => $groups->lastPage(),
            ],
        ]);
    }

    /**
     * Search for prayer sessions by title or description.
     *
     * Excludes ended sessions and does not return anonymous session hosts.
     *
     * @param string $query
     * @param int $perPage
     * @return \Illuminate\Http\JsonResponse
     */
    private function searchSessions(string $query, int $perPage)
    {
        $sessions = PrayerSession::where(function ($q) use ($query) {
            $q->where('title', 'like', "%{$query}%")
              ->orWhere('description', 'like', "%{$query}%");
        })
        ->where('status', '!=', 'ended')
        ->with([
            'host' => function ($q) {
                $q->select(['id', 'username', 'display_name', 'avatar_url', 'headline', 'level']);
            },
        ])
        ->select([
            'id', 'host_id', 'title', 'description', 'purpose',
            'visibility', 'status', 'max_members', 'scheduled_at', 'created_at'
        ])
        ->paginate($perPage);

        // Transform results to anonymize anonymous sessions
        $sessions->getCollection()->transform(function ($session) {
            if ($session->visibility === 'anonymous') {
                unset($session->host);
            }
            return $session;
        });

        return response()->json([
            'success' => true,
            'message' => 'Sessions found',
            'data' => $sessions->items(),
            'meta' => [
                'current_page' => $sessions->currentPage(),
                'per_page' => $sessions->perPage(),
                'total' => $sessions->total(),
                'last_page' => $sessions->lastPage(),
            ],
        ]);
    }
}
