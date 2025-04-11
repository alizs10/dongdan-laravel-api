<?php

namespace App\Http\Controllers\App\Personal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Personal\CreateLimitRequest;
use App\Http\Requests\Personal\UpdateLimitRequest;
use App\Models\PersonalLimit;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LimitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $limits = PersonalLimit::where('user_id', Auth::id())
            ->with('category')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $limits
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */

    // Class "App\Http\Requests\CreateLimitRequest" does not exist
    public function store(CreateLimitRequest $request): JsonResponse
    {
        $limit = PersonalLimit::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'amount' => $request->amount,
            'period' => $request->period,
            'category_id' => $request->category_id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Limit created successfully',
            'data' => $limit
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(PersonalLimit $limit): JsonResponse
    {
        // Check if the limit belongs to the authenticated user
        if ($limit->user_id !== Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        $limit->load('category');

        return response()->json([
            'status' => 'success',
            'data' => $limit
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLimitRequest $request, string $id): JsonResponse
    {
        $limit = PersonalLimit::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $limit->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'محدودیت با موفقیت به‌روزرسانی شد',
            'data' => $limit
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $limit = PersonalLimit::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $limit->delete();

        return response()->json([
            'status' => true,
            'message' => 'محدودیت با موفقیت حذف شد',
        ], 200);
    }
}
