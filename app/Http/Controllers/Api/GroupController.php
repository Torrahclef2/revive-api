<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\CreateGroupPostRequest;
use App\Http\Requests\CreateGroupRequest;
use App\Http\Requests\ReactToPostRequest;
use App\Http\Resources\GroupPostResource;
use App\Http\Resources\GroupResource;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupPost;
use App\Models\GroupPostReaction;
use Illuminate\Http\Request;

class GroupController extends ApiController
{
    /**
     * Get authenticated user's groups.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get groups where user is a member with eager loading
        $groups = Group::whereHas('members', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->with([
            'creator' => function ($q) {
                $q->select(['id', 'username', 'display_name', 'avatar_url']);
            },
        ])
        ->where('is_active', true)
        ->select(['id', 'creator_id', 'name', 'description', 'purpose', 'avatar_url', 'updated_at'])
        ->orderByDesc('updated_at')
        ->get();

        return $this->success(
            GroupResource::collection($groups),
            'User groups retrieved'
        );
    }

    /**
     * Create a new group.
     * 
     * Creator is automatically added as admin member.
     * 
     * @param CreateGroupRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateGroupRequest $request)
    {
        $user = $request->user();

        // Create the group
        $group = Group::create([
            'creator_id' => $user->id,
            'name' => $request->name,
            'purpose' => $request->purpose,
            'description' => $request->description,
            'is_active' => true,
        ]);

        // Auto-add creator as admin member
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => 'admin',
            'joined_at' => now(),
        ]);

        $group->load('creator');

        return $this->created(
            new GroupResource($group),
            'Group created successfully'
        );
    }

    /**
     * Get group details with members and recent posts.
     * 
     * Auth user must be a member of the group.
     * 
     * @param Group $group
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Group $group, Request $request)
    {
        $user = $request->user();

        // Authorization: user must be a member
        if (!$group->isMember($user->id)) {
            return $this->forbidden('You are not a member of this group');
        }

        // Load group data
        $group->load('creator', 'members.user');

        // Get last 20 posts with reactions
        $posts = $group->posts()
            ->with('author', 'reactions')
            ->latest()
            ->take(20)
            ->get();

        return $this->success([
            'group' => new GroupResource($group),
            'recent_posts' => GroupPostResource::collection($posts),
        ], 'Group details retrieved');
    }

    /**
     * Add a member to the group.
     * 
     * Only admin can add members, and new member must be in admin's circles.
     * 
     * @param Group $group
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addMember(Group $group, Request $request)
    {
        $admin = $request->user();

        // Authorization: admin only
        if (!$group->isAdmin($admin)) {
            return $this->forbidden('Only group admins can add members');
        }

        // Validate user_id parameter
        $request->validate([
            'user_id' => ['required', 'uuid', 'exists:users,id'],
        ]);

        $newMemberId = $request->user_id;

        // Validation: cannot add yourself
        if ($newMemberId === $admin->id) {
            return $this->conflict('You are already a member of this group');
        }

        // Validation: check if user already a member
        if ($group->isMember($newMemberId)) {
            return $this->conflict('This user is already a member of the group');
        }

        // Validation: new member must be in admin's circles
        $isInCircle = $admin->isInCircleWith($admin->find($newMemberId));
        if (!$isInCircle) {
            return $this->forbidden('This user is not in your circles');
        }

        // Add member
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $newMemberId,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        return $this->created(
            ['user_id' => $newMemberId, 'role' => 'member'],
            'Member added to group'
        );
    }

    /**
     * Remove a member from the group.
     * 
     * Admin can remove any member (except last admin).
     * Member can remove themselves.
     * 
     * @param Group $group
     * @param GroupMember $member
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeMember(Group $group, GroupMember $member, Request $request)
    {
        $user = $request->user();

        // Authorization: admin can remove anyone, member can remove themselves
        if ($user->id !== $member->user_id && !$group->isAdmin($user)) {
            return $this->forbidden('You cannot remove this member');
        }

        // Validation: cannot remove last admin
        if ($member->isAdmin()) {
            $adminCount = $group->members()->where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return $this->conflict('Cannot remove the last admin from the group');
            }
        }

        $member->delete();

        return $this->noContent();
    }

    /**
     * Get paginated group feed (posts).
     * 
     * Auth user must be a member.
     * 
     * @param Group $group
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function posts(Group $group, Request $request)
    {
        $user = $request->user();

        // Authorization: user must be a member
        if (!$group->isMember($user->id)) {
            return $this->forbidden('You are not a member of this group');
        }

        $perPage = $request->get('per_page', 20);

        // Eager load author and reactions to prevent N+1
        $posts = $group->posts()
            ->with([
                'author' => function ($q) {
                    $q->select(['id', 'username', 'display_name', 'avatar_url', 'level']);
                },
                'reactions' => function ($q) {
                    $q->select(['id', 'post_id', 'user_id', 'reaction', 'created_at']);
                },
            ])
            ->select(['id', 'group_id', 'author_id', 'content', 'created_at'])
            ->latest()
            ->paginate($perPage);

        return $this->paginated(
            GroupPostResource::collection($posts),
            'Group posts retrieved',
            $posts
        );
    }

    /**
     * Create a new post in the group.
     * 
     * @param Group $group
     * @param CreateGroupPostRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createPost(Group $group, CreateGroupPostRequest $request)
    {
        $user = $request->user();

        // Authorization: must be a member
        if (!$group->isMember($user->id)) {
            return $this->forbidden('You are not a member of this group');
        }

        // Create the post
        $post = GroupPost::create([
            'group_id' => $group->id,
            'author_id' => $user->id,
            'content' => $request->content,
        ]);

        // Eager load relations after creation to prevent N+1
        $post->load([
            'author' => function ($q) {
                $q->select(['id', 'username', 'display_name', 'avatar_url', 'level']);
            },
            'reactions' => function ($q) {
                $q->select(['id', 'post_id', 'user_id', 'reaction', 'created_at']);
            },
        ]);

        return $this->created(
            new GroupPostResource($post),
            'Post created successfully'
        );
    }

    /**
     * React to a group post (amen, heart, pray).
     * 
     * Toggle: if already reacted with same type, remove it.
     * Otherwise create/update reaction.
     * 
     * @param Group $group
     * @param GroupPost $post
     * @param ReactToPostRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function react(Group $group, GroupPost $post, ReactToPostRequest $request)
    {
        $user = $request->user();

        // Authorization: must be a member
        if (!$group->isMember($user->id)) {
            return $this->forbidden('You are not a member of this group');
        }

        // Validation: post must belong to group
        if ($post->group_id !== $group->id) {
            return $this->notFound('Post not found in this group');
        }

        $reactionType = $request->reaction;

        // Check for existing reaction from this user
        $existingReaction = $post->reactions()
            ->where('user_id', $user->id)
            ->where('reaction', $reactionType)
            ->first();

        if ($existingReaction) {
            // Toggle: remove if already exists
            $existingReaction->delete();
            $message = 'Reaction removed';
        } else {
            // Remove any other reaction type from this user
            $post->reactions()
                ->where('user_id', $user->id)
                ->delete();

            // Create new reaction
            GroupPostReaction::create([
                'post_id' => $post->id,
                'user_id' => $user->id,
                'reaction' => $reactionType,
            ]);
            $message = 'Reaction added';
        }

        $post->load('reactions');

        return $this->success(
            new GroupPostResource($post),
            $message
        );
    }
}
