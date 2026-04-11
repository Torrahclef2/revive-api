<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\UpdateGroupPostRequest;
use App\Http\Resources\GroupPostResource;
use App\Models\Group;
use App\Models\GroupPost;
use Illuminate\Http\Request;

class GroupPostController extends ApiController
{
    /**
     * Update a group post.
     * 
     * Only author can update their own post.
     * 
     * @param Group $group
     * @param GroupPost $post
     * @param UpdateGroupPostRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Group $group, GroupPost $post, UpdateGroupPostRequest $request)
    {
        $user = $request->user();

        // Validation: post must belong to group
        if ($post->group_id !== $group->id) {
            return $this->notFound('Post not found in this group');
        }

        // Authorization: only author can update
        if ($post->author_id !== $user->id) {
            return $this->forbidden('You can only edit your own posts');
        }

        // Update the post
        $post->update([
            'content' => $request->content,
        ]);

        $post->load('author', 'reactions');

        return $this->success(
            new GroupPostResource($post),
            'Post updated successfully'
        );
    }

    /**
     * Delete a group post.
     * 
     * Author can delete own post, admin can delete any post.
     * 
     * @param Group $group
     * @param GroupPost $post
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Group $group, GroupPost $post, Request $request)
    {
        $user = $request->user();

        // Validation: post must belong to group
        if ($post->group_id !== $group->id) {
            return $this->notFound('Post not found in this group');
        }

        // Authorization: author or admin can delete
        $isAuthor = $post->author_id === $user->id;
        $isAdmin = $group->isAdmin($user);

        if (!$isAuthor && !$isAdmin) {
            return $this->forbidden('You cannot delete this post');
        }

        $post->delete();

        return $this->noContent();
    }
}
