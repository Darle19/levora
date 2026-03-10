<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelGroupOffer extends Model
{
    protected $fillable = [
        'hotel_id',
        'title',
        'check_in_dates',
        'nights',
        'pax_count',
        'rooms_count',
        'room_configuration',
        'nationality',
        'rate_per_night',
        'currency_id',
        'meal_type_id',
        'cancellation_policy',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'check_in_dates' => 'array',
            'rate_per_night' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function mealType(): BelongsTo
    {
        return $this->belongsTo(MealType::class);
    }
}
