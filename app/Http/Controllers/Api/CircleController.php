<?php

namespace App\Http\Controllers\Api;

use App\Events\CircleAccepted;
use App\Events\CircleRequestSent;
use App\Http\Controllers\ApiController;
use App\Http\Requests\RespondToCircleRequest;
use App\Http\Requests\RespondToCircleSuggestionRequest;
use App\Http\Resources\CircleResource;
use App\Http\Resources\CircleSuggestionResource;
use App\Http\Resources\UserResource;
use App\Models\Circle;
use App\Models\CircleSuggestion;
use App\Models\User;
use Illuminate\Http\Request;

class CircleController extends ApiController
{
    /**
     * List user's accepted circles.
     * 
     * Returns list of users in the authenticated user's circle (both sent and received).
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get both sent and received accepted circles with eager loading
        $circles = Circle::where(function ($query) use ($user) {
            $query->where('requester_id', $user->id)
                  ->orWhere('receiver_id', $user->id);
        })
        ->where('status', 'accepted')
        ->with([
            'requester' => function ($q) {
                $q->select(['id', 'username', 'display_name', 'avatar_url', 'headline', 'level']);
            },
            'receiver' => function ($q) {
                $q->select(['id', 'username', 'display_name', 'avatar_url', 'headline', 'level']);
            },
        ])
        ->select(['id', 'requester_id', 'receiver_id', 'status', 'created_at'])
        ->get();

        // Extract the other user from each circle
        $circleUsers = $circles->map(function ($circle) use ($user) {
            return $circle->requester_id === $user->id ? $circle->receiver : $circle->requester;
        });

        return $this->success(
            UserResource::collection($circleUsers),
            'Circles retrieved'
        );
    }

    /**
     * Send a circle request to another user.
     * 
     * @param User $user The user to request a circle connection with
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function request(User $user, Request $request)
    {
        $authUser = $request->user();

        // Validation: cannot request yourself
        if ($user->id === $authUser->id) {
            return $this->conflict('Cannot send a circle request to yourself');
        }

        // Validation: check for existing circle (any direction, pending or accepted)
        $existingCircle = Circle::where(function ($query) use ($authUser, $user) {
            $query->where('requester_id', $authUser->id)
                  ->where('receiver_id', $user->id);
        })
        ->orWhere(function ($query) use ($authUser, $user) {
            $query->where('requester_id', $user->id)
                  ->where('receiver_id', $authUser->id);
        })
        ->first();

        if ($existingCircle) {
            return $this->conflict('A circle connection already exists with this user');
        }

        // Create new circle request
        $circle = Circle::create([
            'requester_id' => $authUser->id,
            'receiver_id' => $user->id,
            'status' => 'pending',
        ]);

        // Fire event for push notification
        CircleRequestSent::dispatch($circle, $user, $authUser);

        return $this->created(
            new CircleResource($circle->load(['requester', 'receiver'])),
            'Circle request sent'
        );
    }

    /**
     * Respond to a circle request (accept or reject).
     * 
     * @param Circle $circle
     * @param RespondToCircleRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function respond(Circle $circle, RespondToCircleRequest $request)
    {
        $authUser = $request->user();

        // Authorization: only receiver can respond
        if ($circle->receiver_id !== $authUser->id) {
            return $this->forbidden('Only the receiver can respond to this request');
        }

        // Only respond to pending requests
        if ($circle->status !== 'pending') {
            return $this->conflict('This circle request has already been responded to');
        }

        // Update circle status
        $action = $request->input('action');
        $circle->update(['status' => $action === 'accept' ? 'accepted' : 'rejected']);

        // Fire event if accepted
        if ($action === 'accept') {
            CircleAccepted::dispatch($circle, $authUser, $circle->requester);
        }

        return $this->success(
            new CircleResource($circle->load(['requester', 'receiver'])),
            "Circle request {$action}ed"
        );
    }

    /**
     * Remove a user from your circles.
     * 
     * @param Circle $circle
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Circle $circle, Request $request)
    {
        $authUser = $request->user();

        // Authorization: both users can remove
        if ($circle->requester_id !== $authUser->id && $circle->receiver_id !== $authUser->id) {
            return $this->forbidden('You cannot remove this circle connection');
        }

        $circle->delete();

        return $this->noContent();
    }

    /**
     * Get pending circle suggestions for authenticated user.
     * 
     * Suggestions based on shared session attendance.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function suggestions(Request $request)
    {
        $user = $request->user();

        // Get pending suggestions for this user
        $suggestions = CircleSuggestion::where('to_user_id', $user->id)
            ->where('status', 'pending')
            ->with(['session', 'fromUser', 'toUser'])
            ->orderByDesc('created_at')
            ->get();

        return $this->success(
            CircleSuggestionResource::collection($suggestions),
            'Circle suggestions retrieved'
        );
    }

    /**
     * Respond to a circle suggestion (accept to create request or dismiss).
     * 
     * @param CircleSuggestion $suggestion
     * @param RespondToCircleSuggestionRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function respondToSuggestion(CircleSuggestion $suggestion, RespondToCircleSuggestionRequest $request)
    {
        $authUser = $request->user();

        // Authorization: only recipient can respond
        if ($suggestion->to_user_id !== $authUser->id) {
            return $this->forbidden('Only the suggestion recipient can respond');
        }

        // Only respond to pending suggestions
        if ($suggestion->status !== 'pending') {
            return $this->conflict('This suggestion has already been responded to');
        }

        $action = $request->input('action');

        // If accepting suggestion, create a circle request
        if ($action === 'accept') {
            // Check if circle already exists
            $existingCircle = Circle::where(function ($query) use ($authUser, $suggestion) {
                $query->where('requester_id', $authUser->id)
                      ->where('receiver_id', $suggestion->from_user_id);
            })
            ->orWhere(function ($query) use ($authUser, $suggestion) {
                $query->where('requester_id', $suggestion->from_user_id)
                      ->where('receiver_id', $authUser->id);
            })
            ->first();

            if (!$existingCircle) {
                // Create circle request from recipient to suggester
                $circle = Circle::create([
                    'requester_id' => $authUser->id,
                    'receiver_id' => $suggestion->from_user_id,
                    'status' => 'pending',
                ]);

                // Fire event
                CircleRequestSent::dispatch($circle, $suggestion->fromUser, $authUser);
            }
        }

        // Update suggestion status
        $suggestion->update(['status' => $action === 'accept' ? 'accepted' : 'dismissed']);

        return $this->success(
            new CircleSuggestionResource($suggestion->load(['session', 'fromUser', 'toUser'])),
            "Suggestion {$action}ed"
        );
    }
}
