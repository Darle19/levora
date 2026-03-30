<?php

// File: app/Http/Requests/Api/SelectFlightRequest.php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SelectFlightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'offer_id' => ['required', 'string'],
        ];
    }
}
