<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_en' => ['sometimes', 'required', 'string', 'max:255'],
            'name_ru' => ['nullable', 'string', 'max:255'],
            'name_uz' => ['nullable', 'string', 'max:255'],
            'country_id' => ['sometimes', 'required', 'integer', 'exists:countries,id'],
            'code' => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
        ];
    }
}
