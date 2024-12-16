<?php

namespace App\Http\Requests;

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

        if ($this->member_id && $this->member_type && $this->member_type == 'App\Models\Contact') {

            return [
                'member_id' => 'required|string|exists:contacts,id',
                'member_type' => 'required|string|in:App\Models\Contact',
            ];
        }

        if ($this->member_id && $this->member_type && $this->member_type == 'App\Models\User') {

            return [
                'member_id' => 'required|string|in:' . $this->user()->id,
                'member_type' => 'required|string|in:App\Models\User',
            ];
        }

        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'scheme' => 'required|string|in:red,blue,green,yellow,purple,orange,rose,gray',
        ];;
    }
}
