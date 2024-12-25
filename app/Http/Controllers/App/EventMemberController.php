<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateEventMemberRequest;
use App\Http\Requests\UpdateEventMemberRequest;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\Request;

class EventMemberController extends Controller
{
    public function get_members(Request $request, $event_id)
    {
        $event = $request->user()->events()->find($event_id);
        if (!$event) {
            return response()->json([
                'message' => 'Event not found',
                'status' => false
            ], 404);
        }

        $members = $event->members()->with(['member'])->get();

        return response()->json([
            'members' => $members,
            'message' => 'Members retrieved successfully',
            'status' => true
        ]);
    }

    public function get_non_members(Request $request, $event_id)
    {
        $event = $request->user()->events()->find($event_id);
        if (!$event) {
            return response()->json([
                'message' => 'Event not found',
                'status' => false
            ], 404);
        }
        $non_members = [];
        $user_contacts = $request->user()->contacts()->get();
        $members = $event->members()->get();

        foreach ($user_contacts as $contact) {
            if (!$members->contains('member_id', $contact->id)) {
                $non_members[] = $contact;
            }
        }

        $self_included = true;
        // check for user it self
        if (!$members->contains('member_id', $request->user()->id)) {
            $self_included = false;
        }

        return response()->json([
            'non_members' => $non_members,
            'self_included' => $self_included,
            'message' => 'Non members retrieved successfully',
            'status' => true
        ]);
    }

    public function create_member(CreateEventMemberRequest $request, $event_id)
    {
        $event = $request->user()->events()->find($event_id);
        if (!$event) {
            return response()->json([
                'message' => 'Event not found',
                'status' => false
            ], 404);
        }

        if ($request->contacts || $request->self_included) {
            $contacts = Contact::findMany($request->contacts);

            $contacts->each(function ($contact) use ($event) {
                $event->members()->firstOrCreate([
                    'member_id' => $contact->id,
                    'member_type' => Contact::class,
                    'name' => $contact->name,
                    'email' => $contact->email,
                    'scheme' => $contact->scheme
                ]);
            });

            if ($request->self_included === 'true') {
                $event->members()->firstOrCreate([
                    'member_id' => $request->user()->id,
                    'member_type' => User::class,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'scheme' => $request->user()->scheme
                ]);
            } else {
                $event->members()->where('member_id', $request->user()->id)->delete();
            }

            return response()->json([
                'message' => 'Members created successfully',
                'members' => $event->members()->get(),
                'status' => true
            ], 200);
        }

        $new_member = $event->members()->firstOrCreate([
            'name' => $request->name,
            'email' => $request->email,
            'scheme' => $request->scheme
        ]);

        return response()->json([
            'member' => $new_member,
            'message' => 'Member created successfully',
            'status' => true
        ], 201);
    }

    public function get_member(Request $request, $event_id, $member_id)
    {
        $event = $request->user()->events()->find($event_id);
        if (!$event) {
            return response()->json([
                'message' => 'Event not found',
                'status' => false
            ], 404);
        }

        $member = $event->members()->with(['member', 'expensesAsPayer', 'expensesAsTransmitter', 'expensesAsReceiver', 'expensesAsContributor'])->find($member_id);
        if (!$member) {
            return response()->json([
                'message' => 'Member not found',
                'status' => false
            ], 404);
        }

        return response()->json([
            'member' => $member,
            'message' => 'Member retrieved successfully',
            'status' => true
        ]);
    }

    public function update_member(UpdateEventMemberRequest $request, $event_id, $member_id)
    {
        $event = $request->user()->events()->find($event_id);
        if (!$event) {
            return response()->json([
                'message' => 'Event not found',
                'status' => false
            ], 404);
        }

        $member = $event->members()->with('member')->find($member_id);

        if (!$member) {
            return response()->json([
                'message' => 'Member not found',
                'status' => false
            ], 404);
        }

        if ($member->member_id && $member->member_type) {
            $member->member()->update([
                'name' => $request->name,
                'email' => $request->email,
                'scheme' => $request->scheme,
            ]);
        }

        $member->update([
            'name' => $request->name,
            'email' => $request->email,
            'scheme' => $request->scheme,
        ]);


        return response()->json([
            'member' => $member->load('member'),
            'message' => 'Member updated successfully',
            'status' => true
        ]);
    }

    public function destroy_member(Request $request, $event_id, $member_id)
    {
        $event = $request->user()->events()->find($event_id);
        if (!$event) {
            return response()->json([
                'message' => 'Event not found',
                'status' => false
            ], 404);
        }

        $member = $event->members()->find($member_id);
        if (!$member) {
            return response()->json([
                'message' => 'Member not found',
                'status' => false
            ], 404);
        }

        $member->delete();

        return response()->json([
            'message' => 'Member deleted successfully',
            'status' => true
        ]);
    }
}
