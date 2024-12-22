<?php

namespace App\Http\Requests;

use App\Rules\EventBelongsToUser;
use Illuminate\Foundation\Http\FormRequest;

class MultiEventRequest extends FormRequest
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
        return [
            'events' => 'required|array',
            'events.*' => ['required', 'string', 'exists:events,id', new EventBelongsToUser($this->user())]
        ];
    }
}
