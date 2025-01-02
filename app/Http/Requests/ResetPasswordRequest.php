<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
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
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string|size:64',
            'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\W).{8,}$/',
            'password_confirmation' => 'required|string|min:8',
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
            'email.required' => 'ایمیل الزامی است',
            'email.email' => 'فرمت ایمیل نامعتبر است',
            'email.exists' => 'این ایمیل در سیستم وجود ندارد',
            'token.required' => 'توکن الزامی است',
            'token.string' => 'توکن باید متن باشد',
            'token.length' => 'توکن باید دقیقاً ۶۴ کاراکتر باشد',
            'password.required' => 'رمز عبور الزامی است',
            'password.string' => 'رمز عبور باید متن باشد',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد',
            'password.confirmed' => 'تأیید رمز عبور مطابقت ندارد',
            'password.regex' => 'رمز عبور باید شامل حروف بزرگ، کوچک و نماد باشد',
            'password_confirmation.required' => 'تأیید رمز عبور الزامی است',
            'password_confirmation.string' => 'تأیید رمز عبور باید متن باشد',
            'password_confirmation.min' => 'تأیید رمز عبور باید حداقل ۸ کاراکتر باشد',
        ];
    }
}
