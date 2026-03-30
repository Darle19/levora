<?php

// File: app/Models/TourTemplateLeg.php

namespace App\Models;

use App\Enums\TimeRange;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TourTemplateLeg extends Model
{
    protected $fillable = [
        'tour_template_id',
        'leg_order',
        'departure_city_id',
        'arrival_city_id',
        'departure_date',
        'arrival_date',
        'preferred_time_range',
        'passenger_count',
    ];

    protected function casts(): array
    {
        return [
            'leg_order' => 'integer',
            'departure_date' => 'date',
            'arrival_date' => 'date',
            'preferred_time_range' => TimeRange::class,
            'passenger_count' => 'integer',
        ];
    }

    // ── Relationships ──

    public function template(): BelongsTo
    {
        return $this->belongsTo(TourTemplate::class, 'tour_template_id');
    }

    public function departureCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'departure_city_id');
    }

    public function arrivalCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'arrival_city_id');
    }

    public function flightSelection(): HasOne
    {
        return $this->hasOne(TourTemplateFlightSelection::class);
    }

    // ── Helpers ──

    public function hasFlightSelected(): bool
    {
        return $this->flightSelection()->exists();
    }
}
