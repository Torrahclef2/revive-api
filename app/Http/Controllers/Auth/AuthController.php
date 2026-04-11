<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends ApiController
{
    /**
     * Register a new user account.
     * 
     * @param RegisterRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterRequest $request)
    {
        // Create new user
        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'gender' => $request->gender,
            'headline' => $request->headline,
            'denomination' => $request->denomination,
            'level' => 'seeker',
            'xp_points' => 0,
            'streak_count' => 0,
            'is_active' => true,
        ]);

        // Create Sanctum token
        $token = $user->createToken('mobile-app')->plainTextToken;

        // Return user data with token
        return $this->created([
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Account registered successfully');
    }

    /**
     * Authenticate user and issue token.
     * 
     * @param LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        // Attempt authentication
        if (!Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            return $this->unauthorized('Invalid email or password');
        }

        // Get authenticated user
        $user = Auth::user();

        // Revoke all existing tokens
        $user->tokens()->delete();

        // Create new Sanctum token
        $token = $user->createToken('mobile-app')->plainTextToken;

        // Return user data with token
        return $this->success([
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Login successful');
    }

    /**
     * Logout current user (revoke current token).
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Revoke current access token
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out successfully');
    }

    /**
     * Get current authenticated user profile.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        return $this->success(
            new UserResource($request->user()),
            'User profile retrieved'
        );
    }
}
