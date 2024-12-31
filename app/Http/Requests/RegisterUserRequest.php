<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Example: Allow only authenticated users to update their own email
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
            // 'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            // 'scheme' => 'required|string|in:red,blue,green,yellow,purple,orange,rose,gray',
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
            'email.required' => 'وارد کردن ایمیل الزامی است',
            'email.string' => 'ایمیل باید متن باشد',
            'email.email' => 'فرمت ایمیل نامعتبر است',
            'email.max' => 'ایمیل نمی‌تواند بیشتر از ۲۵۵ کاراکتر باشد',
            'email.unique' => 'این ایمیل قبلاً ثبت شده است',
            'password.required' => 'وارد کردن رمز عبور الزامی است',
            'password.string' => 'رمز عبور باید متن باشد',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد',
            'password.confirmed' => 'تأیید رمز عبور با رمز عبور مطابقت ندارد',
            'password.regex' => 'رمز عبور باید شامل حروف کوچک، بزرگ و علائم خاص باشد',
            'password_confirmation.required' => 'تأیید رمز عبور الزامی است',
            'password_confirmation.string' => 'تأیید رمز عبور باید متن باشد',
            'password_confirmation.min' => 'تأیید رمز عبور باید حداقل ۸ کاراکتر باشد',
        ];
    }
}
