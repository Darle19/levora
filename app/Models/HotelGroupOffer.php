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
        'date_from',
        'date_to',
        'nights',
        'pax_count',
        'rooms_count',
        'rooms_booked',
        'room_configuration',
        'nationality',
        'rate_tiers',
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
            'date_from' => 'date',
            'date_to' => 'date',
            'rate_tiers' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function getRoomsAvailableAttribute(): int
    {
        return $this->rooms_count - $this->rooms_booked;
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
