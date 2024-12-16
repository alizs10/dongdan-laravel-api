<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateEventMemberRequest;
use App\Http\Requests\UpdateEventMemberRequest;
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

    public function create_member(CreateEventMemberRequest $request, $event_id)
    {
        $event = $request->user()->events()->find($event_id);
        if (!$event) {
            return response()->json([
                'message' => 'Event not found',
                'status' => false
            ], 404);
        }

        if ($request->member_id && $request->member_type && $request->member_type == 'App\Models\Contact') {

            $contact = $request->user()->contacts()->find($request->member_id);
            if (!$contact) {
                return response()->json([
                    'message' => 'Contact not found',
                    'status' => false
                ], 404);
            }

            $already_member = $event->members()->where('member_id', $contact->id)->first();

            if ($already_member) {
                return response()->json([
                    'message' => 'Member already exists',
                    'status' => false
                ], 400);
            }

            $member = $event->members()->create([
                'name' => $contact->name,
                'email' => $contact->email,
                'scheme' => $contact->scheme,
                'member_id' => $request->member_id,
                'member_type' => 'App\Models\Contact'
            ]);
        }

        if ($request->member_id && $request->member_type && $request->member_type == 'App\Models\User') {

            if ($request->member_id != $request->user()->id) {
                return response()->json([
                    'message' => 'You can only add yourself as a member',
                    'status' => false
                ], 422);
            }

            $already_member = $event->members()->where('member_id', $request->user()->id)->first();

            if ($already_member) {
                return response()->json([
                    'message' => 'Member already exists',
                    'status' => false
                ], 400);
            }

            $member = $event->members()->create([
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'scheme' => $request->user()->scheme,
                'member_id' => $request->user()->id,
                'member_type' => 'App\Models\User'
            ]);
        }

        if (!$request->member_id && !$request->member_type) {
            $member = $event->members()->create([
                'name' => $request->name,
                'email' => $request->email,
                'scheme' => $request->scheme,
            ]);
        }

        return response()->json([
            'member' => $member->load('member'),
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
