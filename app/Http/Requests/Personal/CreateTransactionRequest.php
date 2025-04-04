<?php

namespace App\Http\Requests\Personal;

use Illuminate\Foundation\Http\FormRequest;

class CreateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Sanctum middleware handles auth
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:personal_categories,id',
            'is_recurring' => 'required|string|in:true,false',
            'frequency' => 'required_if:is_recurring,true|in:daily,weekly,monthly,yearly',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'نوع تراکنش الزامی است',
            'type.in' => 'نوع باید درآمد یا هزینه باشد',
            'amount.required' => 'مبلغ الزامی است',
            'amount.numeric' => 'مبلغ باید عدد باشد',
            'amount.min' => 'مبلغ نمی‌تواند منفی باشد',
            'date.required' => 'تاریخ الزامی است',
            'date.date' => 'تاریخ باید معتبر باشد',
            'title.required' => 'عنوان الزامی است',
            'title.string' => 'عنوان باید متن باشد',
            'title.max' => 'عنوان نمی‌تواند بیشتر از ۲۵۵ کاراکتر باشد',
            'description.string' => 'توضیحات باید متن باشد',
            'description.max' => 'توضیحات نمی‌تواند بیشتر از ۱۰۰۰ کاراکتر باشد',
            'category_ids.array' => 'دسته‌بندی‌ها باید به صورت آرایه باشند',
            'category_ids.*.exists' => 'دسته‌بندی انتخاب‌شده وجود ندارد',
            'is_recurring.required' => 'وضعیت تکرار الزامی است',
            'is_recurring.in' => 'وضعیت تکرار باید true یا false باشد',
            'frequency.required_if' => 'فرکانس برای تراکنش تکراری الزامی است',
            'frequency.in' => 'فرکانس باید روزانه، هفتگی، ماهانه یا سالانه باشد',
        ];
    }
}
