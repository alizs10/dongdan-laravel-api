<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateEventRequest;
use App\Http\Requests\MultiEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Contact;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use DateTime;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $user_events =  $request->user()->events()->withCount(['members', 'expenses'])->get();

        return response()->json([
            'status' => true,
            'message' => 'events retrieved successfully!',
            'events' => $user_events
        ]);
    }

    public function trashed_events(Request $request)
    {
        $trashed_events =  $request->user()->events()->onlyTrashed()->withCount('members')->get();
        return response()->json([
            'status' => true,
            'message' => 'events retrieved successfully!',
            'trashed_events' => $trashed_events
        ]);
    }

    public function get_event(Request $request, string $slug)
    {
        $event = Event::where('slug', $slug)->first();

        if (!$event) {
            return response()->json([
                'status' => false,
                'message' => 'event not found!'
            ]);
        }

        // تعداد رکوردهای درخواستی در هر صفحه
        $limit = $request->query('limit', 10);
        // cursor: تاریخ آخرین آیتمی که در صفحه قبل دریافت شده و آیدی آن
        $cursor = $request->query('cursor');
        $cursorId = $request->query('cursor_id');

        $query = $event->expenses()
            ->with(['contributors.eventMember', 'payer', 'transmitter', 'receiver'])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

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

        $event->load('members');

        $event->members->each(function ($member) {
            $member->append(['balance', 'balance_status', 'total_expends_amount', 'total_contributions_amount', 'total_sent_amount', 'total_received_amount']);
        });

        return response()->json([
            'status' => true,
            'message' => 'event retrieved successfully!',
            'data' => [
                'event' => $event,
                'event_data' => [
                    'expends_count' => $event->expends_count,
                    'transfers_count' => $event->transfers_count,
                    'total_amount' => $event->total_amount,
                    'max_expend_amount' => $event->max_expend_amount,
                    'max_transfer_amount' => $event->max_transfer_amount,
                    'treasurer' => $event->treasurer,
                ],
                'expenses_data' => [
                    'expenses' => $expenses,
                    'pagination' => [
                        'next_cursor' => $nextCursor,
                        'next_cursor_id' => $nextCursorId,
                        'has_more' => $hasMore
                    ]
                ]
            ]
        ]);
    }

    public function create(CreateEventRequest $request)
    {
        $event = Event::create([
            'name' => $request->name,
            'start_date' => Carbon::parse($request->start_date)->setTimezone('Asia/Tehran')->format('Y-m-d H:i:s'),
            'end_date' => null,
            'label' => $request->label,
            'user_id' => $request->user()->id
        ]);

        if ($request->contact_members && count($request->contact_members) > 0) {
            $contacts = Contact::findMany($request->contact_members);
            foreach ($contacts as $contact) {
                $event->members()->create([
                    'member_id' => $contact->id,
                    'member_type' => Contact::class,
                    'name' => $contact->name,
                    'scheme' => $contact->scheme,
                    'email' => $contact->email,
                ]);
            }
        }

        if ($request->self_included && $request->self_included === 'true') {
            $event->members()->create([
                'member_id' => $request->user()->id,
                'member_type' => User::class,
                'name' => $request->user()->name,
                'scheme' => $request->user()->scheme,
                'email' => $request->user()->email,
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'event created successfully!',
            'event' => $event->load(['members', 'expenses'])->loadCount('members', 'expenses')
        ]);
    }


    public function update(UpdateEventRequest $request, string $id)
    {

        $event = Event::find($id);

        if (!$event) {
            return response()->json([
                'status' => false,
                'message' => 'event not found!'
            ]);
        }

        $event->update([
            'name' => $request->name,
            'start_date' => Carbon::parse($request->start_date)->setTimezone('Asia/Tehran')->format('Y-m-d H:i:s'),
            'label' => $request->label,
        ]);

        // sync contacts: we are getting event members ids, every member except these ids should be deleted.

        if ($request->members && count($request->members) > 0) {
            $event->members()->whereNotIn('id', $request->members)->delete();
        }


        // add contacts as members: we are getting contact ids, make sure they are not already members. then create new members.
        if ($request->contacts && count($request->contacts) > 0) {
            $contacts = Contact::findMany($request->contacts);

            foreach ($contacts as $contact) {
                $event->members()->firstOrCreate([
                    'member_id' => $contact->id,
                    'member_type' => Contact::class,
                ], [
                    'name' => $contact->name,
                    'scheme' => $contact->scheme,
                    'email' => $contact->email,
                ]);
            }
        }

        // add/delete user as member
        if ($request->self_included === 'true') {
            $event->members()->firstOrCreate([
                'member_id' => $request->user()->id,
                'member_type' => User::class,
            ], [
                'name' => $request->user()->name,
                'scheme' => $request->user()->scheme,
                'email' => $request->user()->email,
            ]);
        } else {
            $event->members()->where('member_id', $request->user()->id)->delete();
        }

        return response()->json([
            'status' => true,
            'message' => 'event updated successfully!',
            'event' => $event->load(['members', 'expenses'])->loadCount('members', 'expenses')
        ]);
    }

    public function updateStatus(Request $request, string $id)
    {
        $event = $request->user()->events()->find($id);
        if (!$event) {
            return response()->json([
                'status' => false,
                'message' => 'event not found!'
            ]);
        }

        if ($request->has('end_date')) {
            $endDate = Carbon::parse($request->end_date)->setTimezone('Asia/Tehran')->format('Y-m-d H:i:s');
            $event->update(['end_date' => $endDate]);
        } else {
            $event->update([
                'end_date' => $event->end_date === null ? Carbon::now()->setTimezone('Asia/Tehran')->format('Y-m-d H:i:s') : null,
            ]);
        }

        // Refresh the event without changing the start date
        $event->refresh();

        return response()->json([
            'status' => true,
            'message' => 'event status updated successfully!',
            'end_date' => $event->end_date,
        ]);
    }

    // trash event/events
    public function trash(Request $request, string $id)
    {

        $event = Event::find($id);

        if (!$event) {
            return response()->json([
                'status' => false,
                'message' => 'event not found!'
            ]);
        }

        if ($event->user_id !== $request->user()->id) {
            return response()->json([
                'status' => false,
                'message' => 'you are not authorized to delete this event!'
            ]);
        }

        $event->delete();

        return response()->json([
            'status' => true,
            'message' => 'event moved to trash successfully!'
        ]);
    }

    public function trash_items(MultiEventRequest $request)
    {
        $request->user()->events()->whereIn('id', $request->events)->delete();

        return response()->json([
            'status' => true,
            'message' => 'events moved to trash successfully!'
        ], 200);
    }

    // restore event/events
    public function restore(Request $request, string $id)
    {
        $event = $request->user()->events()->withTrashed()->find($id);
        if (!$event) {
            return response()->json([
                'status' => false,
                'message' => 'event not found!',
            ]);
        }
        $event->restore();
        return response()->json([
            'status' => true,
            'message' => 'event restored successfully!'
        ], 200);
    }

    public function restore_items(MultiEventRequest $request)
    {

        $request->user()->events()->withTrashed()->whereIn('id', $request->events)->restore();

        return response()->json([
            'status' => true,
            'message' => 'events restored successfully!'
        ], 200);
    }


    // delete event/events
    public function delete(Request $request, string $id)
    {
        $event = Event::withTrashed()->find($id);

        if (!$event) {
            return response()->json([
                'status' => false,
                'message' => 'event not found!'
            ]);
        }

        if ($event->user_id !== $request->user()->id) {
            return response()->json([
                'status' => false,
                'message' => 'you are not authorized to delete this event!'
            ]);
        }

        $event->forceDelete();

        return response()->json([
            'status' => true,
            'message' => 'event deleted successfully!'
        ]);
    }

    public function delete_items(MultiEventRequest $request)
    {
        $request->user()->events()->withTrashed()->whereIn('id', $request->events)->forceDelete();

        return response()->json([
            'status' => true,
            'message' => 'events permanently deleted successfully!'
        ], 200);
    }


    // public function predict_test(Request $request, string $id)
    // {
    //     $event = $request->user()->events()->find($id);
    //     if (!$event) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'event not found!'
    //         ]);
    //     }

    //     // Step 1: Aggregate expenses per day
    //     $daily_totals = [];
    //     $start_date = new DateTime($event->start_date);
    //     $end_date = new DateTime();

    //     // Initialize all dates with 0 amount
    //     while ($start_date <= $end_date) {
    //         $daily_totals[$start_date->format('Y-m-d')] = 0;
    //         $start_date->modify('+1 day');
    //     }

    //     // Aggregate expenses per day
    //     foreach ($event->expenses as $entry) {
    //         $date = (new DateTime($entry["date"]))->format('Y-m-d');
    //         $amount = $entry["amount"];
    //         $daily_totals[$date] += $amount;
    //     }

    //     ksort($daily_totals);
    //     $sorted_dates = array_keys($daily_totals);
    //     $daily_expenses = array_values($daily_totals);

    //     // Step 2: Prepare data for regression
    //     $x = range(1, count($daily_expenses));
    //     $y = $daily_expenses;
    //     $n = count($x);

    //     $sum_x = array_sum($x);
    //     $sum_y = array_sum($y);
    //     $sum_xy = 0;
    //     $sum_x_squared = 0;

    //     for ($i = 0; $i < $n; $i++) {
    //         $sum_xy += $x[$i] * $y[$i];
    //         $sum_x_squared += $x[$i] * $x[$i];
    //     }

    //     $denominator = ($n * $sum_x_squared - $sum_x * $sum_x);
    //     if ($denominator == 0) {
    //         return response()->json(["error" => "Not enough variation in expenses."], 400);
    //     }

    //     $m = ($n * $sum_xy - $sum_x * $sum_y) / (float)$denominator;
    //     $b = ($sum_y - $m * $sum_x) / (float)$n;

    //     if ($m < 0) {
    //         $m = 0;
    //     }

    //     $first_date = new DateTime($sorted_dates[0]);
    //     $days_in_month = cal_days_in_month(CAL_GREGORIAN, $first_date->format("m"), $first_date->format("Y"));
    //     $days_provided = count($daily_expenses);
    //     $remaining_days = $days_in_month - $days_provided;

    //     $predicted_expenses = [];
    //     $predicted_remaining = 0;

    //     for ($i = $days_provided + 1; $i <= $days_in_month; $i++) {
    //         $predicted_value = round(max(0, $m * $i + $b), 2);
    //         $predicted_expenses[] = $predicted_value;
    //         $predicted_remaining += $predicted_value;
    //     }

    //     $total_actual = array_sum($daily_expenses);
    //     $total_estimate = $total_actual + $predicted_remaining;

    //     return response()->json([
    //         "daily_expenses" => $daily_expenses,
    //         "estimated_remaining" => round($predicted_remaining, 2),
    //         "total_estimate" => round($total_estimate, 2)
    //     ]);

    // }


    public function predict_test(Request $request, string $id)
    {
        $event = $request->user()->events()->find($id);
        if (!$event) {
            return response()->json(['status' => false, 'message' => 'Event not found!']);
        }

        // Aggregate daily expenses
        $dailyTotals = $this->aggregateDailyExpenses($event);

        // Validate data requirements
        if (count($dailyTotals) < 10) {
            return response()->json(['status' => false, 'message' => 'Need at least 10 days of data']);
        }

        // Convert to array of values in chronological order
        $dailyExpenses = array_values($dailyTotals);

        // Calculate weighted average with exponential decay
        $weightedAverage = $this->calculateWeightedAverage($dailyExpenses);

        // Generate prediction
        $prediction = $this->calculatePrediction($dailyTotals, $weightedAverage);

        return response()->json([
            "daily_expenses" => $dailyExpenses,
            "estimated_remaining" => round($prediction['remaining'], 2),
            "total_estimate" => round($prediction['total'], 2),
            "weighted_average" => round($weightedAverage, 2),
            "confidence_factor" => round($prediction['confidence'], 2)
        ]);
    }

    // Helper function 1: Aggregate daily expenses
    private function aggregateDailyExpenses($event)
    {
        $dailyTotals = [];
        $startDate = new DateTime($event->start_date);
        $endDate = new DateTime();

        // Initialize date range
        while ($startDate <= $endDate) {
            $dailyTotals[$startDate->format('Y-m-d')] = 0;
            $startDate->modify('+1 day');
        }

        // Sum expenses
        foreach ($event->expenses as $entry) {
            $date = (new DateTime($entry["date"]))->format('Y-m-d');
            if (isset($dailyTotals[$date])) {
                $dailyTotals[$date] += $entry["amount"];
            }
        }

        ksort($dailyTotals);
        return $dailyTotals;
    }

    // Helper function 2: Calculate weighted average with exponential decay
    private function calculateWeightedAverage($dailyExpenses)
    {
        $alpha = 0.85; // Controls weight decay (0.8-0.95)
        $weights = [];
        $n = count($dailyExpenses);

        // Generate exponential weights (newest first)
        for ($i = 0; $i < $n; $i++) {
            $weights[] = pow($alpha, $n - $i - 1);
        }

        // Normalize weights
        $totalWeight = array_sum($weights);
        $normalizedWeights = array_map(fn($w) => $w / $totalWeight, $weights);

        // Calculate weighted average
        $weightedSum = 0;
        foreach ($dailyExpenses as $idx => $amount) {
            $weightedSum += $amount * $normalizedWeights[$idx];
        }

        return $weightedSum;
    }

    // Helper function 3: Calculate prediction with confidence
    private function calculatePrediction($dailyTotals, $weightedAverage)
    {
        $firstDate = new DateTime(array_key_first($dailyTotals));
        $currentDate = new DateTime();

        // Calculate remaining days in current month
        $daysInMonth = cal_days_in_month(
            CAL_GREGORIAN,
            $currentDate->format("m"),
            $currentDate->format("Y")
        );

        $daysPassed = $currentDate->diff($firstDate)->days + 1;
        $remainingDays = max($daysInMonth - $daysPassed, 0);

        // Calculate confidence factor (based on data variability)
        $totalActual = array_sum($dailyTotals);
        $predictedRemaining = $weightedAverage * $remainingDays;

        // Confidence calculation (1 - coefficient of variation)
        $variance = array_sum(array_map(
            fn($x) => pow($x - $weightedAverage, 2),
            $dailyTotals
        )) / count($dailyTotals);

        $confidence = $weightedAverage > 0
            ? 1 - (sqrt($variance) / $weightedAverage)
            : 0;

        return [
            'remaining' => $predictedRemaining,
            'total' => $totalActual + $predictedRemaining,
            'confidence' => max(min($confidence, 1), 0)
        ];
    }
}
