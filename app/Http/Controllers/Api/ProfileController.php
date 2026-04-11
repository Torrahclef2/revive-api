<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\PublicProfileResource;
use App\Http\Resources\SessionHistoryResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class ProfileController extends ApiController
{
    /**
     * Get public profile of a user by username.
     * 
     * @param string $username
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $username)
    {
        // Find user by username and ensure active
        $user = User::where('username', $username)
            ->where('is_active', true)
            ->first();

        if (!$user) {
            return $this->notFound('User not found');
        }

        return $this->success(
            new PublicProfileResource($user),
            'Public profile retrieved'
        );
    }

    /**
     * Update authenticated user profile.
     * 
     * @param UpdateProfileRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();

        // Update only allowed fields
        $user->update($request->only([
            'display_name',
            'headline',
            'denomination',
            'location_city',
            'location_country',
            'gender',
        ]));

        return $this->success(
            new UserResource($user),
            'Profile updated successfully'
        );
    }

    /**
     * Upload and resize user avatar.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadAvatar(Request $request)
    {
        // Validate image file
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ], [
            'avatar.required' => 'Please upload an image',
            'avatar.image' => 'File must be an image',
            'avatar.mimes' => 'Image must be JPEG, PNG, or WebP format',
            'avatar.max' => 'Image must not exceed 2MB',
        ]);

        $user = $request->user();
        $file = $request->file('avatar');

        try {
            // Read image from uploaded file
            $image = Image::make($file->stream())
                ->fit(400, 400, function ($constraint) {
                    $constraint->aspectRatio();
                });

            // Create avatars directory if it doesn't exist
            $directory = 'public/avatars';
            if (!Storage::exists($directory)) {
                Storage::makeDirectory($directory);
            }

            // Store as WebP
            $filename = "{$user->id}.webp";
            $path = "avatars/{$filename}";

            // Save to storage
            Storage::put($path, $image->encode('webp', 90));

            // Generate public URL
            $avatarUrl = Storage::url($path);

            // Update user's avatar_url
            $user->update(['avatar_url' => $avatarUrl]);

            return $this->success(
                ['avatar_url' => $avatarUrl],
                'Avatar uploaded successfully'
            );
        } catch (\Exception $e) {
            return $this->serverError('Failed to upload avatar: ' . $e->getMessage());
        }
    }

    /**
     * Get paginated history of sessions hosted or joined by authenticated user.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 15);

        // Get all sessions user is associated with (hosted or joined)
        $sessions = $user->hostedPrayerSessions()
            ->union(
                $user->prayerSessions()
            )
            ->with('members')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return $this->paginated(
            SessionHistoryResource::collection($sessions),
            'Session history retrieved',
            $sessions
        );
    }
}
