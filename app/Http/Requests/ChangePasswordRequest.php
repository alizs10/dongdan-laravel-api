<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
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
            'password' => 'required|string|min:8',
            'new_password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\W).{8,}$/',
            'new_password_confirmation' => 'required|string|min:8',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'password.required' => 'رمز عبور فعلی الزامی است',
            'password.string' => 'رمز عبور باید متن باشد',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد',
            'new_password.required' => 'رمز عبور جدید الزامی است',
            'new_password.string' => 'رمز عبور جدید باید متن باشد',
            'new_password.min' => 'رمز عبور جدید باید حداقل ۸ کاراکتر باشد',
            'new_password.confirmed' => 'تأیید رمز عبور جدید مطابقت ندارد',
            'new_password.regex' => 'رمز عبور باید شامل حروف بزرگ، کوچک و نماد باشد',
            'new_password_confirmation.required' => 'تأیید رمز عبور جدید الزامی است',
            'new_password_confirmation.string' => 'تأیید رمز عبور جدید باید متن باشد',
            'new_password_confirmation.min' => 'تأیید رمز عبور جدید باید حداقل ۸ کاراکتر باشد',
        ];
    }
}
