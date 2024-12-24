<?php

namespace App\Http\Requests;

use App\Rules\ContactBelongsToUser;
use Illuminate\Foundation\Http\FormRequest;

class CreateEventMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        if ($this->contacts || $this->self_included) {

            return [
                'contacts' => 'nullable|array',
                'contacts.*' => ['required', 'string', 'exists:contacts,id', new ContactBelongsToUser($this->user())],
                'self_included' => 'required|in:true,false',
            ];
        }


        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'scheme' => 'required|string|in:red,blue,green,yellow,purple,orange,rose,gray',
        ];;
    }
}
