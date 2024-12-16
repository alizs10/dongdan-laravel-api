<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function get_expenses(Request $request, $event_id)
    {
        $event = $request->user()->events()->find($event_id);

        if (!$event) {
            return response()->json([
                'message' => 'Event not found',
                'status' => false
            ], 404);
        }

        $expenses = $event->expenses()->with(['payer', 'transmitter', 'receiver', 'contributors'])->get();

        return response()->json([
            'expenses' => $expenses,
            'message' => 'Expenses retrieved successfully',
            'status' => true
        ]);
    }

    public function create_expense(CreateExpenseRequest $request, $event_id)
    {

        $event = $request->user()->events()->find($event_id);
        if (!$event) {
            return response()->json([
                'message' => 'Event not found',
                'status' => false
            ], 404);
        }

        $expense = $event->expenses()->create([
            'name' => $request->name,
            'type' => $request->type,
            'description' => $request->description,
            'date' => $request->date,
            'payer_id' => $request->payer_id,
            'transmitter_id' => $request->transmitter_id,
            'receiver_id' => $request->receiver_id,
            'amount' => $request->amount,
        ]);

        if ($request->payer_id && $request->contributors && count($request->contributors) > 0) {
            $expense->contributors()->attach($request->contributors);
        }

        return response()->json([
            'expense' => $expense,
            'message' => 'Expense created successfully',
            'status' => true
        ], 201);
    }

    public function get_expense(Request $request, $event_id, $expense_id)
    {
        $event = $request->user()->events()->find($event_id);

        if (!$event) {
            return response()->json([
                'message' => 'Event not found',
                'status' => false
            ], 404);
        }

        $expense = $event->expenses()->find($expense_id);
        if (!$expense) {
            return response()->json([
                'message' => 'Expense not found',
                'status' => false
            ], 404);
        }

        return response()->json([
            'expense' => $expense->load(['payer', 'transmitter', 'receiver', 'contributors']),
            'message' => 'Expense retrieved successfully',
            'status' => true
        ]);
    }

    public function update_expense(UpdateExpenseRequest $request, $event_id, $expense_id)
    {

        $event = $request->user()->events()->find($event_id);

        if (!$event) {
            return response()->json([
                'message' => 'Event not found',
                'status' => false
            ], 404);
        }

        $expense = $event->expenses()->find($expense_id);

        if (!$expense) {
            return response()->json([
                'message' => 'Expense not found',
                'status' => false
            ], 404);
        }

        $expense->update([
            'name' => $request->name,
            'type' => $request->type,
            'description' => $request->description,
            'date' => $request->date,
            'payer_id' => $request->payer_id,
            'transmitter_id' => $request->transmitter_id,
            'receiver_id' => $request->receiver_id,
            'amount' => $request->amount,
        ]);

        $expense->contributors()->sync($request->contributors);

        return response()->json([
            'expense' => $expense->load(['payer', 'transmitter', 'receiver', 'contributors']),
            'message' => 'Expense updated successfully',
            'status' => true
        ]);
    }

    public function destroy_expense(Request $request, $event_id, $expense_id)
    {
        $event = $request->user()->events()->find($event_id);

        if (!$event) {
            return response()->json([
                'message' => 'Event not found',
                'status' => false
            ], 404);
        }

        $expense = $event->expenses()->find($expense_id);

        if (!$expense) {
            return response()->json([
                'message' => 'Expense not found',
                'status' => false
            ], 404);
        }

        $expense->delete();

        return response()->json([
            'message' => 'Expense deleted successfully',
            'status' => true
        ]);
    }
}
