<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_en',
        'name_ru',
        'name_uz',
        'code',
        'max_adults',
        'max_children',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'max_adults' => 'integer',
            'max_children' => 'integer',
        ];
    }

    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->{"name_$locale"} ?? $this->name_en;
    }

    public function tourPrices(): HasMany
    {
        return $this->hasMany(TourPrice::class);
    }

    public function hotels(): BelongsToMany
    {
        return $this->belongsToMany(Hotel::class, 'hotel_room_type')
            ->withPivot('price_per_night', 'is_active')
            ->withTimestamps();
    }
}
