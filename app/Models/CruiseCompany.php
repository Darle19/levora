<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CruiseCompany extends Model
{
    protected $fillable = [
        'name',
        'name_en',
        'name_ar',
        'name_ru',
        'code',
        'logo',
        'description',
        'description_en',
        'description_ar',
        'description_ru',
        'website',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function ships(): HasMany
    {
        return $this->hasMany(Ship::class);
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
