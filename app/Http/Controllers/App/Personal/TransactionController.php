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
            ->with('category')
            ->get();

        $expandedTransactions = $this->expandRecurringTransactions($transactions);

        return response()->json([
            'status' => true,
            'message' => 'تراکنش‌ها با موفقیت دریافت شدند',
            'data' => $expandedTransactions,
        ], 200);
    }

    /**
     * Fetch a single transaction by ID.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $transaction = PersonalTransaction::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->with('category')
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
            'category_id' => $validated['category_id'] ?? null,
            'is_recurring' => $validated['is_recurring'] === 'true',
            'frequency' => $validated['is_recurring'] === 'true' ? $validated['frequency'] : null,
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تراکنش با موفقیت ایجاد شد',
            'data' => $transaction->load('category'),
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
            'category_id' => $validated['category_id'] ?? null,
            'is_recurring' => $validated['is_recurring'] === 'true',
            'frequency' => $validated['is_recurring'] === 'true' ? $validated['frequency'] : null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تراکنش با موفقیت به‌روزرسانی شد',
            'data' => $transaction->load('category'),
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

    /**
     * Expand recurring transactions up to current date.
     */
    private function expandRecurringTransactions($transactions)
    {
        $expanded = [];
        $now = Carbon::now('Asia/Tehran');

        foreach ($transactions as $transaction) {
            if (!$transaction->is_recurring || !$transaction->frequency) {
                $expanded[] = $transaction;
                continue;
            }

            $startDate = Carbon::parse($transaction->date, 'Asia/Tehran');
            $currentDate = $startDate->copy();

            while ($currentDate <= $now) {
                $expanded[] = (object) [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'date' => $currentDate->format('Y-m-d'),
                    'title' => $transaction->title,
                    'description' => $transaction->description,
                    'category_id' => $transaction->category_id,
                    'category' => $transaction->category,
                    'is_recurring' => $transaction->is_recurring,
                    'frequency' => $transaction->frequency,
                    'user_id' => $transaction->user_id,
                    'created_at' => $transaction->created_at,
                    'updated_at' => $transaction->updated_at,
                ];

                switch ($transaction->frequency) {
                    case 'daily':
                        $currentDate->addDay();
                        break;
                    case 'weekly':
                        $currentDate->addWeek();
                        break;
                    case 'monthly':
                        $currentDate->addMonth();
                        break;
                    case 'yearly':
                        $currentDate->addYear();
                        break;
                }
            }
        }

        return $expanded;
    }
}
