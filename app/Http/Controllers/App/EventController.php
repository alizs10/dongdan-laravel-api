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
use Illuminate\Support\Facades\Log;

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

        $per_page = 10;
        $page = 1;

        $expenses = $event->expenses()
            ->with(['contributors.eventMember', 'payer', 'transmitter', 'receiver'])
            ->orderBy('date', 'desc')
            ->paginate($per_page, ['*'], 'page', $page);

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
                    // 'member_with_most_expends' => $event->member_with_most_expends,
                    // 'member_with_most_transfers' => $event->member_with_most_transfers,
                    'treasurer' => $event->treasurer,
                ],
                'expenses_data' => [
                    'expenses' => $expenses->items(),
                    'pagination' => [
                        'total' => $expenses->total(),
                        'per_page' => $expenses->perPage(),
                        'current_page' => $expenses->currentPage(),
                        'total_pages' => $expenses->lastPage(), // This is the total number of pages
                        'from' => $expenses->firstItem(),
                        'to' => $expenses->lastItem()
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
            'event' => $event->load(['members', 'expenses'])->loadCount('members')
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
            'event' => $event->load(['members', 'expenses'])->loadCount('members')
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

        $event->update([
            'end_date' => $event->end_date === null ? Carbon::now()->setTimezone('Asia/Tehran')->format('Y-m-d H:i:s') : null,
        ]);

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
}
