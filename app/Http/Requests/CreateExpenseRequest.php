<?php

namespace App\Http\Requests;

use App\Rules\MemberBelongsToEvent;
use Illuminate\Foundation\Http\FormRequest;

class CreateExpenseRequest extends FormRequest
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
        $rules = [
            'description' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:expend,transfer'],
            'date' => ['required', 'date']
        ];

        // Conditional rules
        if ($this->input('type') === 'expend') {
            $rules['receiver_id'] = 'prohibited';
            $rules['transmitter_id'] = 'prohibited';
            $rules['payer_id'] = ['required', 'string', 'exists:event_members,id', new MemberBelongsToEvent($this->route('event_id'))];
            $rules['equal_shares'] = ['required', 'boolean'];
            $rules['contributors'] = 'required|array';
            $rules['contributors.*'] = [
                'required',
                'array'
            ];
            $rules['contributors.*.event_member_id'] = [
                'required',
                'string',
                'exists:event_members,id',
                new MemberBelongsToEvent($this->route('event_id'))
            ];
            $rules['contributors.*.amount'] = [
                'required',
                'string',
                'regex:/^[1-9][0-9]*$/'
            ];
        } elseif ($this->input('type') === 'transfer') {
            $rules['amount'] = ['required', 'string', 'regex:/^[1-9][0-9]*$/'];
            $rules['payer_id'] = 'prohibited';
            $rules['contributors'] = 'prohibited';
            $rules['transmitter_id'] = ['required', 'string', 'exists:event_members,id', new MemberBelongsToEvent($this->route('event_id'))];
            $rules['receiver_id'] = ['required', 'string', 'exists:event_members,id', new MemberBelongsToEvent($this->route('event_id'))];
        }

        return $rules;
    }
}
