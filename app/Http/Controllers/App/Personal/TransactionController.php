<?php

namespace App\Http\Controllers\App\Personal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Personal\CreateTransactionRequest;
use App\Http\Requests\Personal\UpdateTransactionRequest;
use App\Models\PersonalTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TransactionController extends Controller
{
    /**
     * Fetch all transactions for the authenticated user with recurrence expansion.
     */
    public function index(Request $request): JsonResponse
    {
        $transactions = PersonalTransaction::where('user_id', $request->user()->id)
            ->with('categories')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'تراکنش‌ها با موفقیت دریافت شدند',
            'data' => $transactions,
        ], 200);
    }

    /**
     * Fetch a single transaction by ID.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $transaction = PersonalTransaction::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->with('categories')
            ->firstOrFail();

        return response()->json([
            'status' => true,
            'message' => 'تراکنش با موفقیت دریافت شد',
            'data' => $transaction,
        ], 200);
    }

    /**
     * Create a new transaction.
     */
    public function store(CreateTransactionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $transaction = PersonalTransaction::create([
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'date' => Carbon::parse($validated['date'])->setTimezone('Asia/Tehran')->format('Y-m-d'),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'is_recurring' => $validated['is_recurring'] === 'true',
            'frequency' => $validated['is_recurring'] === 'true' ? $validated['frequency'] : null,
            'user_id' => $request->user()->id,
            'savings_goal_id' => $validated['savings_goal_id'] ?? null, // Optional savings goal
        ]);

        // Sync categories if provided
        if (isset($validated['category_ids']) && is_array($validated['category_ids'])) {
            $transaction->categories()->sync($validated['category_ids']);
        }

        return response()->json([
            'status' => true,
            'message' => 'تراکنش با موفقیت ایجاد شد',
            'data' => $transaction->load('categories'),
        ], 201);
    }

    /**
     * Update an existing transaction.
     */
    public function update(UpdateTransactionRequest $request, int $id): JsonResponse
    {
        $transaction = PersonalTransaction::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validated();
        $transaction->update([
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'date' => Carbon::parse($validated['date'])->setTimezone('Asia/Tehran')->format('Y-m-d'),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'is_recurring' => $validated['is_recurring'] === 'true',
            'frequency' => $validated['is_recurring'] === 'true' ? $validated['frequency'] : null,
        ]);

        // Sync categories if provided
        if (isset($validated['category_ids']) && is_array($validated['category_ids'])) {
            $transaction->categories()->sync($validated['category_ids']);
        }

        return response()->json([
            'status' => true,
            'message' => 'تراکنش با موفقیت به‌روزرسانی شد',
            'data' => $transaction->load('categories'),
        ], 200);
    }

    /**
     * Delete a transaction.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $transaction = PersonalTransaction::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $transaction->delete();

        return response()->json([
            'status' => true,
            'message' => 'تراکنش با موفقیت حذف شد',
        ], 200);
    }
}
