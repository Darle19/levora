<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TourTemplate extends Model
{
    protected $fillable = [
        'route_name',
        'departure_city_id',
        'total_nights',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'total_nights' => 'integer',
        ];
    }

    public function departureCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'departure_city_id');
    }

    public function stays(): HasMany
    {
        return $this->hasMany(TourTemplateStay::class)->orderBy('stay_order');
    }

    public function flightPaths(): HasMany
    {
        return $this->hasMany(FlightPath::class);
    }

    /**
     * Derive flight legs from city sequence: departure → city1 → city2 → ... → departure.
     * Returns array of ['from_city_id', 'to_city_id', 'day_offset', 'direction'].
     */
    public function deriveLegs(): array
    {
        $stays = $this->stays()->with('city')->get();

        $citySequence = [$this->departure_city_id];
        $nightsSequence = [];
        foreach ($stays as $stay) {
            $citySequence[] = $stay->city_id;
            $nightsSequence[] = $stay->nights;
        }
        $citySequence[] = $this->departure_city_id;

        $legs = [];
        $dayOffset = 0;
        for ($i = 0; $i < count($citySequence) - 1; $i++) {
            $legs[] = [
                'from_city_id' => $citySequence[$i],
                'to_city_id' => $citySequence[$i + 1],
                'day_offset' => $dayOffset,
                'direction' => ($i < count($stays)) ? 'outbound' : 'return',
            ];
            if ($i < count($nightsSequence)) {
                $dayOffset += $nightsSequence[$i];
            }
        }

        return $legs;
    }
}
