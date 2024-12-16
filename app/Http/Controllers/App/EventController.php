<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Contact;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $user_events =  $request->user()->events()->withCount('members')->get();

        return response()->json([
            'status' => true,
            'message' => 'events retrieved successfully!',
            'events' => $user_events
        ]);
    }

    public function get_event(string $slug)
    {
        $event = Event::where('slug', $slug)->first();

        if (!$event) {
            return response()->json([
                'status' => false,
                'message' => 'event not found!'
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'event retrieved successfully!',
            'event' => $event->load(['members', 'expenses'])
        ]);
    }

    public function create(CreateEventRequest $request)
    {
        $event = Event::create([
            'name' => $request->name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
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
            'event' => $event->load(['members', 'expenses'])
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
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'label' => $request->label,
        ]);

        // add new contact members
        // if ($request->add_contact_members && count($request->add_contact_members) > 0) {
        //     foreach ($request->add_contact_members as $member_id) {
        //         $contact = Contact::find($member_id);
        //         $event->members()->create([
        //             'member_id' => $contact->id,
        //             'member_type' => Contact::class,
        //             'name' => $contact->name,
        //             'scheme' => $contact->scheme,
        //             'email' => $contact->email,
        //         ]);
        //     }
        // }

        // remove contact members
        // if ($request->remove_contact_members && count($request->remove_contact_members) > 0) {
        //     foreach ($request->remove_contact_members as $member_id) {
        //         $event->members()->where('member_id', $member_id)->delete();
        //     }
        // }


        // update self included
        // if ($request->self_included) {
        //     if ($request->self_included === 'true') {
        //         $event->members()->firstOrCreate([
        //             'member_id' => $request->user()->id,
        //             'member_type' => User::class,
        //             'name' => $request->user()->name,
        //             'scheme' => $request->user()->scheme,
        //             'email' => $request->user()->email,
        //         ]);
        //     } else {
        //         $event->members()->where('member_id', $request->user()->id)->delete();
        //     }
        // }


        return response()->json([
            'status' => true,
            'message' => 'event updated successfully!',
            'event' => $event->load(['members', 'expenses'])
        ]);
    }

    public function delete(Request $request, string $id)
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

    public function destroy(Request $request, string $id)
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
}
