<?php

namespace App\Imports;

use App\Models\Hotel;
use App\Models\HotelCategory;
use App\Models\Resort;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class HotelImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row): ?Hotel
    {
        $resort = Resort::where('name_en', $row['resort'])->first();
        $category = HotelCategory::where('stars', $row['stars'])->first();

        if (!$resort || !$category) {
            return null;
        }

        return new Hotel([
            'name' => $row['name'],
            'name_en' => $row['name'],
            'name_ru' => $row['name_ru'] ?? $row['name'],
            'name_uz' => $row['name_uz'] ?? $row['name'],
            'resort_id' => $resort->id,
            'hotel_category_id' => $category->id,
            'address' => $row['address'] ?? null,
            'phone' => $row['phone'] ?? null,
            'email' => $row['email'] ?? null,
            'rating' => $row['rating'] ?? null,
            'is_active' => true,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'resort' => 'required|string',
            'stars' => 'required|integer|min:1|max:5',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'rating' => 'nullable|numeric|min:0|max:5',
        ];
    }
}
