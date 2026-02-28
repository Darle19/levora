<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MealType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_en',
        'name_ar',
        'name_ru',
        'code',
        'description',
        'description_en',
        'description_ar',
        'description_ru',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function hotels(): BelongsToMany
    {
        return $this->belongsToMany(Hotel::class, 'hotel_meal_type');
    }

    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->{"name_$locale"} ?? $this->name_en ?? $this->attributes['name'];
    }

    public function getDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->{"description_$locale"} ?? $this->description_en ?? $this->attributes['description'] ?? null;
    }
}
