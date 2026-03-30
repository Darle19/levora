<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AdditionalService extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'city_id',
        'name_en',
        'name_ru',
        'name_uz',
        'description_en',
        'description_ru',
        'description_uz',
        'service_type',
        'price',
        'currency_id',
        'is_per_person',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_per_person' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function scopeForCity($query, int $cityId)
    {
        return $query->where('city_id', $cityId);
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('city_id');
    }

    public function tours(): BelongsToMany
    {
        return $this->belongsToMany(Tour::class, 'tour_additional_service')
            ->withPivot('price_override', 'is_included')
            ->withTimestamps();
    }

    public function localizedName(): string
    {
        $locale = app()->getLocale();
        return $this->{"name_{$locale}"} ?? $this->name_en;
    }
}
