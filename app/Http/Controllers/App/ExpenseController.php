<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateExpenseRequest;
use App\Http\Requests\MultiExpensesRequest;
use App\Http\Requests\UpdateExpenseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

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

        // تعداد رکوردهای درخواستی در هر صفحه
        $limit = $request->query('limit', 10);
        // cursor: تاریخ آخرین آیتمی که در صفحه قبل دریافت شده و آیدی آن
        $cursor = $request->query('cursor');
        $cursorId = $request->query('cursor_id');
        // آیدی‌های رکوردهایی که باید حذف شوند
        $excludeIds = $request->query('exclude_ids', []);

        if (is_string($excludeIds)) {
            $excludeIds = explode(',', $excludeIds);
        }

        Log::info($excludeIds);

        $query = $event->expenses()
            ->with(['payer', 'transmitter', 'receiver', 'contributors.eventMember'])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        // حذف رکوردهای مورد نظر
        if (!empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }

        // اگر cursor داشته باشیم، فقط رکوردهایی که تاریخ کوچکتر از cursor دارند را می‌گیریم
        // یا اگر تاریخ برابر است، آیدی کوچکتر از cursor_id داشته باشند
        if ($cursor && $cursorId) {
            $query->where(function ($q) use ($cursor, $cursorId) {
                $q->where('date', '<', Carbon::parse($cursor))
                    ->orWhere(function ($q) use ($cursor, $cursorId) {
                        $q->where('date', '=', Carbon::parse($cursor))
                            ->where('id', '<', $cursorId);
                    });
            });
        }

        // یک رکورد بیشتر می‌گیریم تا بفهمیم آیا صفحه بعدی وجود دارد
        $expenses = $query->take($limit + 1)->get();

        // اگر تعداد نتایج بیشتر از limit باشد، یعنی صفحه بعدی وجود دارد
        $hasMore = $expenses->count() > $limit;
        // رکورد اضافی را حذف می‌کنیم
        $expenses = $expenses->take($limit);

        // cursor بعدی را از تاریخ و آیدی آخرین آیتم می‌گیریم
        $nextCursor = $hasMore ? $expenses->last()->date : null;
        $nextCursorId = $hasMore ? $expenses->last()->id : null;

        return response()->json([
            'status' => true,
            'message' => 'Expenses retrieved successfully',
            'data' => [
                'expenses' => $expenses,
                'pagination' => [
                    'next_cursor' => $nextCursor,
                    'next_cursor_id' => $nextCursorId,
                    'has_more' => $hasMore
                ]
            ]
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
            'event_data' => [
                'expends_count' => $event->expends_count,
                'transfers_count' => $event->transfers_count,
                'total_amount' => $event->total_amount,
                'max_expend_amount' => $event->max_expend_amount,
                'max_transfer_amount' => $event->max_transfer_amount,
                // 'member_with_most_expends' => $event->member_with_most_expends,
                // 'member_with_most_transfers' => $event->member_with_most_transfers,
                'treasurer' => $event->treasurer,

            ],
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
            'event_data' => [
                'expends_count' => $event->expends_count,
                'transfers_count' => $event->transfers_count,
                'total_amount' => $event->total_amount,
                'max_expend_amount' => $event->max_expend_amount,
                'max_transfer_amount' => $event->max_transfer_amount,
                // 'member_with_most_expends' => $event->member_with_most_expends,
                // 'member_with_most_transfers' => $event->member_with_most_transfers,
                'treasurer' => $event->treasurer,

            ],
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
            'status' => true,
            'event_data' => [
                'expends_count' => $event->expends_count,
                'transfers_count' => $event->transfers_count,
                'total_amount' => $event->total_amount,
                'max_expend_amount' => $event->max_expend_amount,
                'max_transfer_amount' => $event->max_transfer_amount,
                // 'member_with_most_expends' => $event->member_with_most_expends,
                // 'member_with_most_transfers' => $event->member_with_most_transfers,
                'treasurer' => $event->treasurer,

            ],
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
            'message' => 'expenses permanently deleted successfully!',
            'event_data' => [
                'expends_count' => $event->expends_count,
                'transfers_count' => $event->transfers_count,
                'total_amount' => $event->total_amount,
                'max_expend_amount' => $event->max_expend_amount,
                'max_transfer_amount' => $event->max_transfer_amount,
                // 'member_with_most_expends' => $event->member_with_most_expends,
                // 'member_with_most_transfers' => $event->member_with_most_transfers,
                'treasurer' => $event->treasurer,

            ],
        ], 200);
    }

    public function filter_expenses(Request $request, $event_id)
    {
        $event = $request->user()->events()->find($event_id);
        if (!$event) {
            return response()->json([
                'message' => 'Event not found',
                'status' => false
            ], 404);
        }

        $query = $event->expenses()
            ->with(['contributors.eventMember', 'payer', 'transmitter', 'receiver']);

        // فیلتر بر اساس نوع هزینه
        if ($request->type && $request->type !== 'any') {
            $query->where('type', $request->type);
        }

        // فیلتر بر اساس مبلغ
        if ($request->min_amount) {
            $query->where('amount', '>=', $request->min_amount);
        }
        if ($request->max_amount) {
            $query->where('amount', '<=', $request->max_amount);
        }

        // فیلتر بر اساس تاریخ
        if ($request->start_date) {
            $query->where('date', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->where('date', '<=', $request->end_date);
        }

        // فیلتر بر اساس پرداخت‌کننده
        if ($request->payer_id && $request->type !== 'transfer') {
            $query->where('payer_id', $request->payer_id);
        }

        // فیلتر بر اساس انتقال‌دهنده
        if ($request->transmitter_id && $request->type !== 'expend') {
            $query->where('transmitter_id', $request->transmitter_id);
        }

        // فیلتر بر اساس دریافت‌کننده
        if ($request->receiver_id && $request->type !== 'expend') {
            $query->where('receiver_id', $request->receiver_id);
        }

        // فیلتر بر اساس مشارکت‌کنندگان
        if ($request->contributor_ids && $request->type !== 'transfer') {
            $contributor_ids = explode(',', $request->contributor_ids);
            $query->whereHas('contributors', function ($q) use ($contributor_ids) {
                $q->whereIn('event_member_id', $contributor_ids);
            });
        }

        // مرتب‌سازی بر اساس تاریخ نزولی
        $query->orderBy('date', 'desc')->orderBy('id', 'desc');

        // تعداد رکوردهای درخواستی در هر صفحه
        $limit = $request->query('limit', 10);
        // cursor: تاریخ آخرین آیتمی که در صفحه قبل دریافت شده و آیدی آن
        $cursor = $request->query('cursor');
        $cursorId = $request->query('cursor_id');

        // اگر cursor داشته باشیم، فقط رکوردهایی که تاریخ کوچکتر از cursor دارند را می‌گیریم
        // یا اگر تاریخ برابر است، آیدی کوچکتر از cursor_id داشته باشند
        if ($cursor && $cursorId) {
            $query->where(function ($q) use ($cursor, $cursorId) {
                $q->where('date', '<', Carbon::parse($cursor))
                    ->orWhere(function ($q) use ($cursor, $cursorId) {
                        $q->where('date', '=', Carbon::parse($cursor))
                            ->where('id', '<', $cursorId);
                    });
            });
        }

        // یک رکورد بیشتر می‌گیریم تا بفهمیم آیا صفحه بعدی وجود دارد
        $expenses = $query->take($limit + 1)->get();

        // اگر تعداد نتایج بیشتر از limit باشد، یعنی صفحه بعدی وجود دارد
        $hasMore = $expenses->count() > $limit;
        // رکورد اضافی را حذف می‌کنیم
        $expenses = $expenses->take($limit);

        // cursor بعدی را از تاریخ و آیدی آخرین آیتم می‌گیریم
        $nextCursor = $hasMore ? $expenses->last()->date : null;
        $nextCursorId = $hasMore ? $expenses->last()->id : null;

        return response()->json([
            'status' => true,
            'message' => 'Expenses filtered successfully',
            'data' => [
                'expenses' => $expenses,
                'pagination' => [
                    'next_cursor' => $nextCursor,
                    'next_cursor_id' => $nextCursorId,
                    'has_more' => $hasMore
                ]
            ]
        ]);
    }
}
