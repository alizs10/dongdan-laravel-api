<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\VerifyEmailRequest;
use App\Models\User;
use App\Notifications\PasswordChangedPersian;
use App\Notifications\ResetPasswordLinkPersian;
use App\Notifications\VerifyEmailPersian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

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

        // $token = $user->createToken('auth_token', ['*'], now()->addDay())->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'User registered successfully!',
            'user' => $user
            // 'token' => $token
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
        $token = $user->createToken('auth_token', ['*'], now()->addDay())->plainTextToken;
        $refreshToken = Str::random(60);

        if (!$user->email_verified_at) {
            $user->sendEmailVerificationNotification();
        }

        DB::table('refresh_tokens')->insert([
            'user_id' => $user->id,
            'token' => $refreshToken,
            'expires_at' => now()->addDays(30), // Longer than access token
            'created_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Logged in successfully!',
            'user' => $user,
            'token' => $token,
            'refresh_token' => $refreshToken
        ], 200)
            ->cookie('token', $token, 60 * 24, '/', null, false, false, false) // 1 day
            ->cookie('refresh_token', $refreshToken, 60 * 24 * 30, '/', null, false, false, false); // 30 days
    }

    public function refresh(Request $request)
    {
        $refreshToken = $request->input('refresh_token');
        $token = DB::table('refresh_tokens')
            ->where('token', $refreshToken)
            ->where('expires_at', '>', now())
            ->first();

        if (!$token) {
            return response()->json(['error' => 'Invalid or expired refresh token'], 401);
        }

        $user = User::find($token->user_id);
        $newAccessToken = $user->createToken('auth_token', ['*'], now()->addDay())->plainTextToken;

        // Optional: Rotate refresh token to extend session
        $newRefreshToken = Str::random(60);
        DB::table('refresh_tokens')->where('token', $refreshToken)->update([
            'token' => $newRefreshToken,
            'expires_at' => now()->addDays(30),
            'updated_at' => now(),
        ]);

        return response()->json([
            'token' => $newAccessToken,
            'refresh_token' => $newRefreshToken,
        ])
            ->cookie('token', $newAccessToken, 60 * 24, '/', null, false, false, false) // Secure, HttpOnly
            ->cookie('refresh_token', $newRefreshToken, 60 * 24 * 30, '/', null, false, false, false);
    }

    public function logout(Request $request)
    {
        $refreshToken = $request->input('refresh_token');
        $request->user()->currentAccessToken()->delete();
        if ($refreshToken) {
            DB::table('refresh_tokens')->where('token', $refreshToken)->delete();
        }
        return response()->json([
            'status' => true,
            'message' => 'Successfully logged out!'
        ], 200);
    }


    // handle password functions

    public function forgot_password(ForgotPasswordRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        // Generate reset password token
        // $token = Password::createToken($user);
        // $status = Password::sendResetLink($request->only('email'));

        $status = Password::sendResetLink(
            $request->only('email')
        );

        // $user->notify(new ResetPasswordLinkPersian($token));

        return $status === Password::RESET_LINK_SENT ? response()->json([
            'status' => true,
            'message' => 'لینک بازیابی رمز عبور به ایمیل شما ارسال شد'
        ]) : response()->json([
            'status' => false,
            'message' => 'لینک بازیابی رمز عبور ارسال نشد'
        ]);
    }

    public function reset_password(ResetPasswordRequest $request)
    {
        $user = User::where('email', $request->email)->firstOrFail();

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();
                $user->tokens()->delete();
                // event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            $user->notify(new PasswordChangedPersian());
        }

        return $status === Password::PASSWORD_RESET ? response()->json([
            'status' => true,
            'message' => 'رمز عبور با موفقیت تغییر کرد'
        ], 200) : response()->json([
            'status' => false,
            'message' => 'عملیات تغییر رمز عبور با خطا مواجه شد. لطفاً لینک بازیابی رمز عبور را بررسی کنید یا درخواست لینک جدید نمایید'
        ], 400);
    }


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

    // google
    public function google_redirect()
    {
        return response()->json([
            'url' => Socialite::driver('google')->stateless()->redirect()->getTargetUrl()
        ], 200);
    }

    public function google_callback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->with(['code' => $request->code])
                ->user();

            $user = User::firstOrCreate(
                ['email' => $googleUser->email],
                [
                    'name' => $googleUser->name,
                    'google_id' => $googleUser->id,
                    'email_verified_at' => now(),
                    'password' => Hash::make(Str::random(16))
                ]
            );
            $user->settings()->create();
            $token = $user->createToken('google-token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
