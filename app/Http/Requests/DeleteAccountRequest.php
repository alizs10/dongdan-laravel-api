<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteAccountRequest extends FormRequest
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
            'password' => 'required|string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\W).{8,}$/',
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
            'password.required' => 'وارد کردن رمز عبور الزامی است',
            'password.string' => 'رمز عبور باید متن باشد',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد',
            'password.regex' => 'رمز عبور باید شامل حروف کوچک، بزرگ و علائم خاص باشد',
        ];
    }
}
