<?php

namespace App\Http\Controllers\App\Personal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Personal\CreateSavingsGoalRequest;
use App\Http\Requests\Personal\UpdateSavingsGoalRequest;
use App\Models\PersonalSavingsGoal;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavingsGoalController extends Controller
{
    /**
     * Fetch all savings goals for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $goals = PersonalSavingsGoal::where('user_id', $request->user()->id)
            ->select('id', 'name', 'target_amount', 'due_date', 'created_at', 'updated_at')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'هدف‌های پس‌انداز با موفقیت دریافت شدند',
            'data' => $goals,
        ], 200);
    }

    /**
     * Fetch a single savings goal by ID.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $goal = PersonalSavingsGoal::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->select('id', 'name', 'target_amount', 'due_date', 'created_at', 'updated_at')
            ->firstOrFail();

        return response()->json([
            'status' => true,
            'message' => 'هدف پس‌انداز با موفقیت دریافت شد',
            'data' => $goal,
        ], 200);
    }

    /**
     * Create a new savings goal.
     */
    public function store(CreateSavingsGoalRequest $request): JsonResponse
    {
        $goal = PersonalSavingsGoal::create([
            'name' => $request->validated()['name'],
            'target_amount' => $request->validated()['target_amount'],
            'due_date' => $request->validated()['due_date'] ? Carbon::parse($request->validated()['due_date'])->setTimezone('Asia/Tehran')->format('Y-m-d') : null,
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'هدف پس‌انداز با موفقیت ایجاد شد',
            'data' => $goal,
        ], 201);
    }

    /**
     * Update an existing savings goal.
     */
    public function update(UpdateSavingsGoalRequest $request, int $id): JsonResponse
    {
        $goal = PersonalSavingsGoal::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $goal->update([
            'name' => $request->validated()['name'],
            'target_amount' => $request->validated()['target_amount'],
            'due_date' => $request->validated()['due_date'] ? Carbon::parse($request->validated()['due_date'])->setTimezone('Asia/Tehran')->format('Y-m-d') : null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'هدف پس‌انداز با موفقیت به‌روزرسانی شد',
            'data' => $goal,
        ], 200);
    }

    /**
     * Delete a savings goal.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $goal = PersonalSavingsGoal::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $goal->delete();

        return response()->json([
            'status' => true,
            'message' => 'هدف پس‌انداز با موفقیت حذف شد',
        ], 200);
    }
}
