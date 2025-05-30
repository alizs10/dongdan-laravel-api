<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateContactRequest;
use App\Http\Requests\MultiContactRequest;
use App\Http\Requests\UpdateContactRequest;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $contacts = $request->user()->contacts;

        return response()->json([
            'status' => true,
            'message' => 'contacts retrieved successfully!',
            'contacts' => $contacts->load('eventMemberShips', 'eventMemberShips.event')
        ]);
    }

    public function trashed_contacts(Request $request)
    {
        $trashed_contacts = $request->user()->contacts()->onlyTrashed()->get();
        return response()->json([
            'status' => true,
            'message' => 'trashed contacts retrieved successfully!',
            'trashed_contacts' => $trashed_contacts
        ]);
    }

    public function create(CreateContactRequest $request)
    {
        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarName = 'avatar' . time() . '.' . $request->avatar->extension();
            $avatarDirectory = public_path('avatars');
            if (!file_exists($avatarDirectory)) {
                mkdir($avatarDirectory, 0755, true);
            }
            $request->avatar->move($avatarDirectory, $avatarName);

            // Full path to the avatar URL
            $avatarPath = asset('avatars/' . $avatarName);
        }

        $contact = $request->user()->contacts()->create([
            'name' => $request->name,
            'email' => $request->email,
            'scheme' => $request->scheme,
            'avatar' => $avatarPath
        ]);

        return response()->json([
            'status' => true,
            'message' => 'contact created successfully!',
            'contact' => $contact
        ]);
    }

    public function update(UpdateContactRequest $request, string $id)
    {
        $contact = $request->user()->contacts()->find($id);

        if (!$contact) {
            return response()->json([
                'status' => false,
                'message' => 'contact not found!',
            ]);
        }

        $avatarUrl = $contact->avatar;

        if ($request->hasFile('avatar')) {
            // Delete old avatar if it exists
            if ($avatarUrl && file_exists(public_path(parse_url($avatarUrl, PHP_URL_PATH)))) {
                unlink(public_path(parse_url($avatarUrl, PHP_URL_PATH)));
            }

            $avatarName = 'avatar' . time() . '.' . $request->avatar->extension();
            $avatarDirectory = public_path('avatars');
            if (!file_exists($avatarDirectory)) {
                mkdir($avatarDirectory, 0755, true);
            }
            $request->avatar->move($avatarDirectory, $avatarName);

            // Full path to the avatar URL
            $avatarUrl = asset('avatars/' . $avatarName);
        }

        $contact->update([
            'name' => $request->name,
            'email' => $request->email,
            'scheme' => $request->scheme,
            'avatar' => $avatarUrl
        ]);

        return response()->json([
            'status' => true,
            'message' => 'contact updated successfully!',
            'contact' => $contact
        ]);
    }

    public function get_contact(Request $request, string $id)
    {
        $contact = $request->user()->contacts()->find($id);

        if (!$contact) {
            return response()->json([
                'status' => false,
                'message' => 'contact not found!',
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'contact retrieved successfully!',
            'contact' => $contact->load('eventMemberships')
        ]);
    }

    public function trash(Request $request, $id)
    {
        $contact = $request->user()->contacts()->find($id);
        if (!$contact) {
            return response()->json([
                'status' => false,
                'message' => 'contact not found!',
            ]);
        }
        $contact->delete();

        return response()->json([
            'status' => true,
            'message' => 'contact moved to trash successfully!'
        ]);
    }

    public function delete(Request $request, string $id)
    {
        $contact = $request->user()->contacts()->withTrashed()->find($id);

        if (!$contact) {
            return response()->json([
                'status' => false,
                'message' => 'contact not found!',
            ]);
        }

        $contact->forceDelete();

        return response()->json([
            'status' => true,
            'message' => 'contact permanently deleted successfully!'
        ]);
    }

    public function trash_items(MultiContactRequest $request)
    {
        $request->user()->contacts()->whereIn('id', $request->contacts)->delete();

        return response()->json([
            'status' => true,
            'message' => 'contacts moved to trash successfully!'
        ], 200);
    }

    public function delete_items(MultiContactRequest $request)
    {
        $request->user()->contacts()->withTrashed()->whereIn('id', $request->contacts)->forceDelete();

        return response()->json([
            'status' => true,
            'message' => 'contacts permanently deleted successfully!'
        ], 200);
    }

    public function restore(Request $request, string $id)
    {
        $contact = $request->user()->contacts()->withTrashed()->find($id);
        if (!$contact) {
            return response()->json([
                'status' => false,
                'message' => 'contact not found!',
            ]);
        }
        $contact->restore();
        return response()->json([
            'status' => true,
            'message' => 'contact restored successfully!'
        ], 200);
    }

    public function restore_items(MultiContactRequest $request)
    {

        $request->user()->contacts()->withTrashed()->whereIn('id', $request->contacts)->restore();

        return response()->json([
            'status' => true,
            'message' => 'contacts restored successfully!'
        ], 200);
    }
}
