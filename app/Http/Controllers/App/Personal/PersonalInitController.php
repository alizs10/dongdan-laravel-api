<?php

namespace App\Http\Controllers\App\Personal;

use App\Http\Controllers\Controller;
use App\Models\PersonalCategory;
use App\Models\PersonalLimit;
use App\Models\PersonalSavingsGoal;
use App\Models\PersonalTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PersonalInitController extends Controller
{
    /**
     * Fetch all necessary personal finance data for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Fetch categories
        $categories = PersonalCategory::where('user_id', $userId)
            ->select('id', 'name', 'created_at', 'updated_at')
            ->withCount('transactions')
            ->get();

        // Fetch savings goals
        $savingsGoals = PersonalSavingsGoal::where('user_id', $userId)
            ->with('transaction')
            ->select('id', 'name', 'target_amount', 'due_date', 'created_at', 'updated_at')
            ->get();

        // Fetch personal limits
        $limits = PersonalLimit::where('user_id', $userId)
            ->select('id', 'name', 'amount', 'period', 'category_id', 'created_at', 'updated_at')
            ->with('category:id,name')
            ->get();

        // Fetch and expand transactions
        $transactions = PersonalTransaction::where('user_id', $userId)
            ->with('categories', 'savingsGoal')
            ->get();

        // Calculate budget (incomes - expenses)
        $budget = $this->calculateBudget($transactions);

        // Calculate savings progress
        $savingsProgress = $this->calculateSavingsProgress($savingsGoals, $budget);

        return response()->json([
            'status' => true,
            'message' => 'داده‌های مالی شخصی با موفقیت دریافت شدند',
            'data' => [
                'categories' => $categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'transaction_count' => $category->transactions_count,
                        'created_at' => $category->created_at,
                        'updated_at' => $category->updated_at,
                    ];
                }),
                'savings_goals' => $savingsGoals->map(function ($goal) use ($savingsProgress) {
                    return [
                        'id' => $goal->id,
                        'name' => $goal->name,
                        'target_amount' => $goal->target_amount,
                        'due_date' => $goal->due_date,
                        'current_amount' => $savingsProgress[$goal->id]['current_amount'] ?? 0,
                        'progress_percentage' => $savingsProgress[$goal->id]['progress_percentage'] ?? 0,
                        'status' => $goal->transaction ? true : false, // Assuming status is based on transaction existence
                        'created_at' => $goal->created_at,
                        'updated_at' => $goal->updated_at,
                    ];
                }),
                'limits' => $limits->map(function ($limit) {
                    return [
                        'id' => $limit->id,
                        'name' => $limit->name,
                        'amount' => $limit->amount,
                        'period' => $limit->period,
                        'category_id' => $limit->category_id,
                        'category' => $limit->category ? [
                            'id' => $limit->category->id,
                            'name' => $limit->category->name,
                        ] : null,
                        'created_at' => $limit->created_at,
                        'updated_at' => $limit->updated_at,
                    ];
                }),
                'transactions' => $transactions,
                'budget' => $budget,
            ],
        ], 200);
    }

    /**
     * Calculate budget as incomes minus expenses.
     *
     * @param array|object $transactions
     * @return float
     */
    private function calculateBudget($transactions): float
    {
        $transactionsArray = $transactions->toArray();

        $incomes = array_sum(
            array_map(
                fn($t) => $t['type'] === 'income' ? $t['amount'] : 0,
                $transactionsArray
            )
        );
        $expenses = array_sum(
            array_map(
                fn($t) => $t['type'] === 'expense' ? $t['amount'] : 0,
                $transactionsArray
            )
        );

        return $incomes - $expenses;
    }

    /**
     * Calculate progress for each savings goal based on budget.
     *
     * @param $savingsGoals
     * @param float $budget
     * @return array
     */
    private function calculateSavingsProgress($savingsGoals, float $budget): array
    {
        $progress = [];
        foreach ($savingsGoals as $goal) {
            $currentAmount = min($budget, $goal->target_amount); // Cap at target
            $progress[$goal->id] = [
                'current_amount' => max(0, $currentAmount), // No negative progress
                'progress_percentage' => $goal->target_amount > 0
                    ? round(($currentAmount / $goal->target_amount) * 100, 2)
                    : 0,
            ];
        }
        return $progress;
    }
}
