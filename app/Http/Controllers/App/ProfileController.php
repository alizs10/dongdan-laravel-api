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

        // check if the password is correct
        if (!password_verify($request->password, $request->user()->password)) {
            return response()->json([
                'status' => false,
                'message' => 'رمز عبور اشتباه است!',
            ], 401);
        }

        // delete the user
        $request->user()->delete();

        return response()->json([
            'status' => true,
            'message' => 'حساب شما با موفقیت حذف شد!',
        ], 200);
    }
}
