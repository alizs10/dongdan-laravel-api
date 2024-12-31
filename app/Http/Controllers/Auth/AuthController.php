<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterUserRequest;
use App\Models\User;
use App\Notifications\VerifyEmailPersian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(RegisterUserRequest $request)
    {
        $user = User::create([
            'name' => explode('@', $request->email)[0],
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $user->settings()->create();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'User registered successfully!',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid login credentials'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        if (!$user->email_verified_at) {
            $user->sendEmailVerificationNotification();
        }

        return response()->json([
            'status' => true,
            'message' => 'Logged in successfully!',
            'user' => $user,
            'token' => $token
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Successfully logged out!'
        ], 200);
    }

    public function sendVerificationEmail(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'status' => false,
                'message' => 'Email already verified'
            ], 400);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'status' => true,
            'message' => 'Verification link sent to your email'
        ], 200);
    }

    // public function verifyEmail(Request $request)
    // {
    //     $user = User::find($request->id);

    //     if (!$user) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'User not found'
    //         ], 404);
    //     }

    //     if (!hash_equals((string) $request->hash, sha1($user->getEmailForVerification()))) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Invalid verification link'
    //         ], 400);
    //     }

    //     if ($user->hasVerifiedEmail()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Email already verified'
    //         ], 400);
    //     }

    //     if ($user->markEmailAsVerified()) {
    //         event(new Verified($user));
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Email verified successfully'
    //     ], 200);
    // }
}
