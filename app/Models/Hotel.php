<?php

namespace App\Models;

use App\Traits\HasLocalizedDescription;
use App\Traits\HasLocalizedName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Hotel extends Model
{
    use HasFactory, HasLocalizedName, HasLocalizedDescription;

    protected $fillable = [
        'name',
        'name_en',
        'name_uz',
        'name_ru',
        'resort_id',
        'hotel_category_id',
        'address',
        'phone',
        'email',
        'website',
        'description',
        'description_en',
        'description_uz',
        'description_ru',
        'images',
        'latitude',
        'longitude',
        'amenities',
        'is_active',
        'rating',
        'price_per_person',
        'currency_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'images' => 'array',
            'amenities' => 'array',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'rating' => 'decimal:2',
            'price_per_person' => 'decimal:2',
        ];
    }

    public function resort(): BelongsTo
    {
        return $this->belongsTo(Resort::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(HotelCategory::class, 'hotel_category_id');
    }

    public function mealTypes(): BelongsToMany
    {
        return $this->belongsToMany(MealType::class, 'hotel_meal_type');
    }

    public function amenityTypes(): BelongsToMany
    {
        return $this->belongsToMany(HotelAmenityType::class, 'hotel_hotel_amenity_type');
    }

    public function roomTypes(): BelongsToMany
    {
        return $this->belongsToMany(RoomType::class, 'hotel_room_type')
            ->withPivot('price_per_night', 'is_active')
            ->withTimestamps();
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

}
