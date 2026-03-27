<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateGroupRequest;
use App\Models\Group;
use App\Models\GroupMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @group Groups
 *
 * Create and manage prayer/study groups.
 */
class GroupController extends Controller
{
    /**
     * Create Group
     *
     * Create a new group. The authenticated user becomes the owner.
     *
     * @bodyParam name string required The group name. Example: Morning Prayers
     * @response 201 scenario="Created" {"group":{"id":1,"name":"Morning Prayers","owner_id":1}}
     */
    public function createGroup(CreateGroupRequest $request): JsonResponse
    {
        $group = Group::create([
            'name'     => $request->name,
            'owner_id' => Auth::id(),
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id'  => Auth::id(),
            'role'     => 'owner',
        ]);

        return response()->json([
            'group' => $group->load('members'),
        ], 201);
    }

    /**
     * Join Group
     *
     * Join an existing group as a member.
     *
     * @urlParam id integer required The group ID. Example: 1
     * @response 200 scenario="Joined" {"message":"Joined group successfully.","group":{"id":1,"name":"Morning Prayers"}}
     * @response 422 scenario="Already a member" {"message":"You are already a member of this group."}
     */
    public function joinGroup(int $id): JsonResponse
    {
        $group = Group::findOrFail($id);

        $alreadyMember = GroupMember::where('group_id', $id)
            ->where('user_id', Auth::id())
            ->exists();

        if ($alreadyMember) {
            return response()->json(['message' => 'You are already a member of this group.'], 422);
        }

        GroupMember::create([
            'group_id' => $id,
            'user_id'  => Auth::id(),
            'role'     => 'member',
        ]);

        return response()->json([
            'message' => 'Joined group successfully.',
            'group'   => $group,
        ]);
    }

    /**
     * My Groups
     *
     * Return all groups the authenticated user belongs to, with member counts.
     *
     * @response 200 scenario="Success" {"groups":[{"id":1,"name":"Morning Prayers","members_count":5}]}
     */
    public function getUserGroups(): JsonResponse
    {
        $groups = Auth::user()
            ->groups()
            ->withCount('members')
            ->get();

        return response()->json(['groups' => $groups]);
    }
}
