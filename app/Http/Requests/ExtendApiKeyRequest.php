<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExtendApiKeyRequest extends FormRequest
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
            'days' => 'required|integer|min:1',
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
            'days.required' => 'عدد الأيام مطلوب',
            'days.integer' => 'عدد الأيام يجب أن يكون رقماً صحيحاً',
            'days.min' => 'عدد الأيام يجب أن يكون 1 على الأقل',
        ];
    }
} 