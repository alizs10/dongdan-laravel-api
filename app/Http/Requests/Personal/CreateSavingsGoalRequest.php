<?php

namespace App\Http\Requests\Personal;

use Illuminate\Foundation\Http\FormRequest;

class CreateSavingsGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Sanctum middleware handles auth
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'target_amount' => 'required|numeric|min:0',
            'due_date' => 'nullable|date|after_or_equal:today',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام هدف پس‌انداز الزامی است',
            'name.string' => 'نام باید متن باشد',
            'name.max' => 'نام نمی‌تواند بیشتر از ۲۵۵ کاراکتر باشد',
            'target_amount.required' => 'مبلغ هدف الزامی است',
            'target_amount.numeric' => 'مبلغ باید عدد باشد',
            'target_amount.min' => 'مبلغ نمی‌تواند منفی باشد',
            'due_date.date' => 'تاریخ باید معتبر باشد',
            'due_date.after_or_equal' => 'تاریخ نمی‌تواند قبل از امروز باشد',
        ];
    }
}
