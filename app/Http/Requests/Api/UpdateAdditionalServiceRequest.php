<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdditionalServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'required', 'string', 'max:50', 'unique:additional_services,code,' . $this->route('service')?->id],
            'name_en' => ['sometimes', 'required', 'string', 'max:255'],
            'name_ru' => ['nullable', 'string', 'max:255'],
            'name_uz' => ['nullable', 'string', 'max:255'],
            'description_en' => ['nullable', 'string'],
            'description_ru' => ['nullable', 'string'],
            'description_uz' => ['nullable', 'string'],
            'service_type' => ['sometimes', 'required', 'string', 'in:transfer,excursion,insurance,other'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'currency_id' => ['nullable', 'integer', 'exists:currencies,id'],
            'is_per_person' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
