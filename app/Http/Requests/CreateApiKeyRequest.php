<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateApiKeyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'permissions' => 'array',
            'permissions.*' => 'string',
            'expires_at' => 'nullable|date|after:now',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'اسم المفتاح مطلوب',
            'name.string' => 'اسم المفتاح يجب أن يكون نصاً',
            'name.max' => 'اسم المفتاح يجب أن لا يتجاوز 255 حرفاً',
            'permissions.array' => 'الصلاحيات يجب أن تكون مصفوفة',
            'permissions.*.string' => 'كل صلاحية يجب أن تكون نصاً',
            'expires_at.date' => 'تاريخ انتهاء الصلاحية يجب أن يكون تاريخاً صحيحاً',
            'expires_at.after' => 'تاريخ انتهاء الصلاحية يجب أن يكون بعد التاريخ الحالي',
        ];
    }
} 