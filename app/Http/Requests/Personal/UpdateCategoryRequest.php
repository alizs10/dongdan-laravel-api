<?php

namespace App\Http\Requests\Personal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true; // Sanctum middleware ensures authentication
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $userId = $this->user()->id;
        $categoryId = $this->route('id');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('personal_categories', 'name')
                    ->where('user_id', $userId)
                    ->ignore($categoryId),
            ],
        ];
    }

    /**
     * Get custom messages for validation errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => 'نام دسته‌بندی الزامی است',
            'name.string' => 'نام دسته‌بندی باید متن باشد',
            'name.max' => 'نام دسته‌بندی نمی‌تواند بیشتر از ۲۵۵ کاراکتر باشد',
            'name.unique' => 'این نام دسته‌بندی قبلاً ثبت شده است',
        ];
    }
}
