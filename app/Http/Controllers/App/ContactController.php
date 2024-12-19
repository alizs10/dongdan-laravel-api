<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateContactRequest;
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
            'contacts' => $contacts
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
        $contact = $request->user()->contacts()->create([
            'name' => $request->name,
            'email' => $request->email,
            'scheme' => $request->scheme
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

        $contact->update([
            'name' => $request->name,
            'email' => $request->email,
            'scheme' => $request->scheme
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
            'contact' => $contact
        ]);
    }

    public function delete(Request $request, $id)
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

    public function destroy(Request $request, string $id)
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
}
