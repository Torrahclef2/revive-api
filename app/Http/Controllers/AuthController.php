<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @group Authentication
 *
 * Endpoints for registering, logging in, and guest access.
 */
class AuthController extends Controller
{
    /**
     * Register
     *
     * Create a new user account and receive an auth token.
     *
     * @unauthenticated
     * @response 201 scenario="Success" {"user":{"id":1,"name":"John Doe","username":"johndoe","email":"john@example.com","avatar":null,"headline":null,"level":"Disciple","streak":0},"token":"1|abc123"}
     * @response 422 scenario="Validation error" {"message":"The email has already been taken.","errors":{"email":["The email has already been taken."]}}
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
     * Login
     *
     * Authenticate with an email address or username and receive an auth token.
     *
     * @unauthenticated
     * @bodyParam login string required The user's email address or username. Example: johndoe
     * @bodyParam password string required The user's password. Example: secret123
     * @response 200 scenario="Success" {"user":{"id":1,"name":"John Doe","username":"johndoe"},"token":"1|abc123"}
     * @response 401 scenario="Invalid credentials" {"message":"Invalid credentials."}
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
     * Guest Login
     *
     * Create a temporary anonymous user account (e.g. for joining a session without registering).
     * Returns a token that can be upgraded to a full account later.
     *
     * @unauthenticated
     * @response 201 scenario="Success" {"user":{"id":5,"name":"Guest_XKDPQR","email":null,"level":"Disciple","streak":0},"token":"2|xyz789"}
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
