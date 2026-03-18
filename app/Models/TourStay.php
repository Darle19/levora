<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourStay extends Model
{
    protected $fillable = [
        'tour_id',
        'stay_order',
        'city_id',
        'hotel_id',
        'resort_id',
        'nights',
        'meal_type_id',
        'price_per_person',
        'currency_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'stay_order' => 'integer',
            'nights' => 'integer',
            'price_per_person' => 'decimal:2',
        ];
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function resort(): BelongsTo
    {
        return $this->belongsTo(Resort::class);
    }

    public function mealType(): BelongsTo
    {
        return $this->belongsTo(MealType::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
