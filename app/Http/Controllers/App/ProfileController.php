<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteAccountRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\UpdateUserSettingsRequest;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'status' => true,
            'message' => 'Profile retrieved successfully!',
            'profile' => $request->user()->load('settings')
        ], 200);
    }

    public function update(ProfileUpdateRequest $request)
    {
        $user = $request->user();
        $user->name = $request->name;
        if ($user->email !== $request->email) {
            $user->email_verified_at = null;
            $user->email = $request->email;
        }
        $user->scheme = $request->scheme;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully!',
            'profile' => $user->load('settings')
        ], 200);
    }

    public function get_settings(Request $request)
    {
        return response()->json([
            'status' => true,
            'message' => 'Settings retrieved successfully!',
            'settings' => $request->user()->settings
        ], 200);
    }

    public function update_settings(UpdateUserSettingsRequest $request)
    {
        $user = $request->user();
        $user->settings()->update([
            'show_as_me' => $request->show_as_me,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Settings updated successfully!',
            'settings' => $user->settings
        ], 200);
    }

    public function delete_account(DeleteAccountRequest $request)
    {
        $user = $request->user();

        // Check if the password is correct
        if (!password_verify($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'رمز عبور اشتباه است!',
            ], 401);
        }

        // Delete the avatar if it exists
        if ($user->avatar) {
            $avatarPath = public_path('avatars/' . basename($user->avatar));
            if (file_exists($avatarPath)) {
                unlink($avatarPath);
            }
        }

        // Delete the user
        $user->delete();

        return response()->json([
            'status' => true,
            'message' => 'حساب شما با موفقیت حذف شد!',
        ], 200);
    }

    public function upload_avatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = $request->user();

        // Delete the old avatar if it exists
        if ($user->avatar) {
            $oldAvatarPath = public_path('avatars/' . basename($user->avatar));
            if (file_exists($oldAvatarPath)) {
                unlink($oldAvatarPath);
            }
        }

        $avatarName = $user->id . '_avatar' . time() . '.' . $request->avatar->extension();
        $avatarDirectory = public_path('avatars');
        if (!file_exists($avatarDirectory)) {
            mkdir($avatarDirectory, 0755, true);
        }
        $request->avatar->move($avatarDirectory, $avatarName);

        // Full path to the avatar URL
        $avatarUrl = asset('avatars/' . $avatarName);

        $user->avatar = $avatarUrl;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Avatar uploaded successfully!',
            'avatar' => $avatarUrl,
        ], 200);
    }

    public function delete_avatar(Request $request)
    {
        $user = $request->user();
        $avatarPath = public_path('avatars/' . basename($user->avatar));

        if (file_exists($avatarPath)) {
            unlink($avatarPath);
        }

        $user->avatar = null;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Avatar deleted successfully!',
        ], 200);
    }
}
