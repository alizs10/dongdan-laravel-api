<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Http\Requests\VerifyEmailRequest;
use App\Models\User;
use App\Notifications\VerifyEmailPersian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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


    // handle password functions
    public function change_password(ChangePasswordRequest $request)
    {
        if (!Hash::check($request->password, $request->user()->password)) {
            return response()->json([
                'status' => false,
                'message' => 'کلمه عبور فعلی اشتباه است'
            ], 401);
        }

        $request->user()->update([
            'password' => bcrypt($request->new_password)
        ]);

        return response()->json([
            'status' => true,
            'message' => 'کلمه عبور با موفقیت تغییر کرد'
        ], 200);
    }


    // verify email
    public function send_verification_email(Request $request)
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

    public function verify_email(VerifyEmailRequest $request)
    {
        $user = User::findOrFail($request->id);

        $signature = hash_hmac('sha256', $request->id . $user->email . $request->expires, config('app.key'));

        if ($signature !== $request->signature) {
            return response()->json([
                'status' => false,
                'message' => 'امضا نامعتبر است'
            ], 401);
        }

        if (now()->timestamp > $request->expires) {
            return response()->json([
                'status' => false,
                'message' => 'لینک تایید منقضی شده است'
            ], 401);
        }

        if (!hash_equals((string) $request->hash, sha1($user->email))) {
            return response()->json([
                'status' => false,
                'message' => 'لینک تایید نامعتبر است'
            ], 400);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'status' => true,
                'message' => 'حساب کاربری قبلا تایید شده است'
            ], 200);
        }

        $user->markEmailAsVerified();

        // event(new Verified($user));

        return response()->json([
            'status' => true,
            'message' => 'حساب کاربری با موفقیت تایید شد'
        ], 200);
    }
}
