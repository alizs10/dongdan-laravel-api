<?php

namespace App\Http\Requests\Personal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLimitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Sanctum middleware handles auth; allow validated users
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'period' => ['sometimes', Rule::in(['weekly', 'monthly', 'yearly'])],
            'category_id' => ['sometimes', 'nullable', 'exists:personal_categories,id,user_id,' . $this->user()->id],
        ];
    }

    /**
     * Get custom Persian validation messages.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'name.string' => 'نام باید رشته باشد',
            'name.max' => 'نام نباید بیش از ۲۵۵ کاراکتر باشد',
            'amount.numeric' => 'مقدار باید عددی باشد',
            'amount.min' => 'مقدار باید حداقل ۰ باشد',
            'period.in' => 'دوره باید یکی از هفتگی، ماهانه یا سالانه باشد',
            'category_id.exists' => 'دسته‌بندی انتخاب‌شده معتبر نیست',
        ];
    }
}
