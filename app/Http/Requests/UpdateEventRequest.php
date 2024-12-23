<?php

namespace App\Http\Requests;

use App\Models\Event;
use App\Rules\ContactBelongsToUser;
use App\Rules\MemberBelongsToEvent;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $event_id = $this->route('id');
        $event = Event::find($event_id);

        return $event && $event->user_id === $this->user()->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'label' => 'required|string|max:255',
            'members' => 'nullable|array',
            'members.*' => ['required', 'string', 'exists:event_members,id', new MemberBelongsToEvent($this->route('id'))],
            'contacts' => 'nullable|array',
            'contacts.*' => ['required', 'string', 'exists:contacts,id', new ContactBelongsToUser($this->user())],
        ];
    }
}
