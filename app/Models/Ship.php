<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ship extends Model
{
    protected $fillable = [
        'name',
        'name_en',
        'name_ar',
        'name_ru',
        'cruise_company_id',
        'code',
        'capacity',
        'year_built',
        'description',
        'description_en',
        'description_ar',
        'description_ru',
        'images',
        'amenities',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'capacity' => 'integer',
            'year_built' => 'integer',
            'images' => 'array',
            'amenities' => 'array',
        ];
    }

    public function cruiseCompany(): BelongsTo
    {
        return $this->belongsTo(CruiseCompany::class);
    }

    public function cruiseRoutes(): HasMany
    {
        return $this->hasMany(CruiseRoute::class);
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
