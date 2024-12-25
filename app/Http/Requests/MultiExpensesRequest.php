<?php

namespace App\Http\Requests;

use App\Rules\ExpenseBelongsToEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class MultiExpensesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $event_id = $this->route('event_id');
        $event = $this->user()->events()->find($event_id);

        return $event !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $event_id = $this->route('event_id');
        $event = $this->user()->events()->find($event_id);

        if (!$event) {
            throw ValidationException::withMessages([
                'event' => ['Event not found'],
            ]);
        }

        return [
            'expenses' => ['required', 'array', 'min:1'],
            'expenses.*' => [
                'required',
                'string',
                'exists:expenses,id',
                new ExpenseBelongsToEvent($event)
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'expenses.required' => 'At least one expense is required.',
            'expenses.array' => 'Expenses must be provided as an array.',
            'expenses.*.required' => 'Each expense ID is required.',
            'expenses.*.exists' => 'One or more expense IDs are invalid.',
        ];
    }
}
