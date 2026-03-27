<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateGroupRequest;
use App\Models\Group;
use App\Models\GroupMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class GroupController extends Controller
{
    /**
     * Create a new group and register the creator as its owner.
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
     * Join an existing group as a regular member.
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
     * Return all groups the authenticated user belongs to.
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
