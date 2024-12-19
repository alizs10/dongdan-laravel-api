<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TokenValidationController extends Controller
{
    public function __invoke(Request $request)
    {
        return response()->json([
            'valid' => true,
            'user' => $request->user(),
            'expires_at' => $request->user()->currentAccessToken()->expires_at
        ]);
    }
}
