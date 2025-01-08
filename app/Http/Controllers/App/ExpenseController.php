<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateExpenseRequest;
use App\Http\Requests\MultiExpensesRequest;
use App\Http\Requests\UpdateExpenseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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

        $expenses = $event->expenses()->with(['payer', 'transmitter', 'receiver', 'contributors.eventMember'])->get();
        return response()->json([
            'expenses' => $expenses,
            'message' => 'Expenses retrieved successfully',
            'status' => true
        ]);
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
            'expense' => $expense->load(['payer', 'transmitter', 'receiver', 'contributors.eventMember']),
            'message' => 'Expense retrieved successfully',
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

        if ($request->type === 'transfer') {
            $expense = $event->expenses()->create([
                'type' => $request->type,
                'description' => $request->description,
                'date' => Carbon::parse($request->date)->setTimezone('Asia/Tehran')->format('Y-m-d H:i:s'),
                'transmitter_id' => $request->transmitter_id,
                'receiver_id' => $request->receiver_id,
                'equal_shares' => false,
                'amount' => intval($request->amount),
            ]);
        } else {

            $total_amount = 0;

            foreach ($request->contributors as $contributor) {
                $total_amount += intval($contributor['amount']);
            }

            $expense = $event->expenses()->create([
                'type' => $request->type,
                'description' => $request->description,
                'date' => Carbon::parse($request->date)->setTimezone('Asia/Tehran')->format('Y-m-d H:i:s'),
                'payer_id' => $request->payer_id,
                'equal_shares' => $request->equal_shares,
                'amount' => $total_amount
            ]);
            foreach ($request->contributors as $contributor) {
                $expense->contributors()->create([
                    'event_member_id' => $contributor['event_member_id'],
                    'amount' => $contributor['amount']
                ]);
            }
        }


        return response()->json([
            'expense' => $expense->load(['contributors.eventMember', 'payer', 'transmitter', 'receiver']),
            'message' => 'Expense created successfully',
            'status' => true
        ], 201);
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

        if ($request->type === 'transfer') {

            if ($expense->type === 'expend') {
                $expense->contributors()->delete();
            }

            $expense->update([
                'type' => $request->type,
                'description' => $request->description,
                'date' => Carbon::parse($request->date)->setTimezone('Asia/Tehran')->format('Y-m-d H:i:s'),
                'payer_id' => null,
                'transmitter_id' => $request->transmitter_id,
                'receiver_id' => $request->receiver_id,
                'equal_shares' => false,
                'amount' => intval($request->amount),
            ]);
        } else {

            $total_amount = 0;

            foreach ($request->contributors as $contributor) {
                $total_amount += intval($contributor['amount']);
            }

            $expense->update([
                'type' => $request->type,
                'description' => $request->description,
                'date' => Carbon::parse($request->date)->setTimezone('Asia/Tehran')->format('Y-m-d H:i:s'),
                'payer_id' => $request->payer_id,
                'transmitter_id' => null,
                'receiver_id' => null,
                'equal_shares' => $request->equal_shares,
                'amount' => $total_amount
            ]);

            // Delete contributors that are not in the request
            $expense->contributors()->whereNotIn('event_member_id', collect($request->contributors)->pluck('event_member_id'))->delete();

            // Update or create contributors from request
            foreach ($request->contributors as $contributor) {
                $expense->contributors()->updateOrCreate(
                    ['event_member_id' => $contributor['event_member_id']],
                    ['amount' => $contributor['amount']]
                );
            }
        }

        $expense->refresh();

        return response()->json([
            'expense' => $expense->load(['payer', 'transmitter', 'receiver', 'contributors.eventMember']),
            'message' => 'Expense updated successfully',
            'status' => true
        ], 200);
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

    public function delete_items(MultiExpensesRequest $request, $event_id)
    {
        $event = $request->user()->events()->find($event_id);
        if (!$event) {
            return response()->json([
                'message' => 'Event not found',
                'status' => false
            ], 404);
        }

        $event->expenses()->whereIn('id', $request->expenses)->forceDelete();

        return response()->json([
            'status' => true,
            'message' => 'expenses permanently deleted successfully!'
        ], 200);
    }
}
