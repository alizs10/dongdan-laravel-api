<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'status' => true,
            'message' => 'Profile retrieved successfully!',
            'user' => $request->user()
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
            'user' => $user
        ], 200);
    }
}
