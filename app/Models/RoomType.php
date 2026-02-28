<?php

namespace App\Models;

use App\Traits\HasLocalizedName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomType extends Model
{
    use HasFactory, HasLocalizedName;

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
