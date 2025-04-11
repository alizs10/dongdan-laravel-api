<?php

namespace App\Http\Requests\Personal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateLimitRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'period' => ['required', Rule::in(['weekly', 'monthly', 'yearly'])],
            'category_id' => ['nullable', 'exists:personal_categories,id,user_id,' . $this->user()->id],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'نام الزامی است',
            'name.string' => 'نام باید رشته باشد',
            'name.max' => 'نام نباید بیش از ۲۵۵ کاراکتر باشد',
            'amount.required' => 'مقدار الزامی است',
            'amount.numeric' => 'مقدار باید عددی باشد',
            'amount.min' => 'مقدار باید حداقل ۰ باشد',
            'period.required' => 'دوره الزامی است',
            'period.in' => 'دوره باید یکی از هفتگی، ماهانه یا سالانه باشد',
            'category_id.exists' => 'دسته‌بندی انتخاب‌شده معتبر نیست',
        ];
    }
}
