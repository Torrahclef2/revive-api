<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Register a new user and return an auth token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'     => $request->name,
            'username' => $request->username,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Authenticate an existing user and return an auth token.
     * Accepts either an email address or a username in the "login" field.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $login    = $request->input('login');
        $password = $request->input('password');

        // Determine whether the login value looks like an email
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($field, $login)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    /**
     * Create a temporary guest user with a generated name and no credentials.
     * Useful for anonymous prayer participation before account creation.
     */
    public function guestLogin(): JsonResponse
    {
        $guestName = 'Guest_' . Str::upper(Str::random(6));

        $user = User::create([
            'name'  => $guestName,
            'level' => 'Disciple',
        ]);

        $token = $user->createToken('guest_token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ], 201);
    }
}
