<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Tour extends Model
{
    use HasFactory;

    protected $fillable = [
        'tour_type_id',
        'program_type_id',
        'country_id',
        'resort_id',
        'hotel_id',
        'transport_type_id',
        'departure_city_id',
        'currency_id',
        'meal_type_id',
        'nights',
        'price',
        'date_from',
        'date_to',
        'adults',
        'children',
        'is_available',
        'is_hot',
        'instant_confirmation',
        'no_stop_sale',
        'child_bed_separate',
        'comfortable_seats',
        'markup_percent',
    ];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'is_hot' => 'boolean',
            'instant_confirmation' => 'boolean',
            'no_stop_sale' => 'boolean',
            'child_bed_separate' => 'boolean',
            'comfortable_seats' => 'boolean',
            'price' => 'decimal:2',
            'markup_percent' => 'decimal:2',
            'date_from' => 'date',
            'date_to' => 'date',
            'nights' => 'integer',
            'adults' => 'integer',
            'children' => 'integer',
        ];
    }

    public function tourType(): BelongsTo
    {
        return $this->belongsTo(TourType::class);
    }

    public function programType(): BelongsTo
    {
        return $this->belongsTo(ProgramType::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function resort(): BelongsTo
    {
        return $this->belongsTo(Resort::class);
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function transportType(): BelongsTo
    {
        return $this->belongsTo(TransportType::class);
    }

    public function departureCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'departure_city_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function mealType(): BelongsTo
    {
        return $this->belongsTo(MealType::class);
    }

    public function bookings(): MorphMany
    {
        return $this->morphMany(Booking::class, 'bookable');
    }

    public function tourPrices(): HasMany
    {
        return $this->hasMany(TourPrice::class);
    }

    public function flights(): BelongsToMany
    {
        return $this->belongsToMany(Flight::class, 'tour_flight')
            ->withPivot('direction');
    }

    public function outboundFlights(): BelongsToMany
    {
        return $this->flights()->wherePivot('direction', 'outbound');
    }

    public function returnFlights(): BelongsToMany
    {
        return $this->flights()->wherePivot('direction', 'return');
    }

    public function additionalServices(): BelongsToMany
    {
        return $this->belongsToMany(AdditionalService::class, 'tour_additional_service')
            ->withPivot('price_override', 'is_included')
            ->withTimestamps();
    }

    public function getEffectiveMarkupPercent(): float
    {
        if ($this->markup_percent !== null) {
            return (float) $this->markup_percent;
        }

        return (float) Setting::getValue('tour_markup_percent', 15.00);
    }
}
