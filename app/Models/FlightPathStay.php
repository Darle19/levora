<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlightPathStay extends Model
{
    protected $fillable = [
        'flight_path_id',
        'city_id',
        'stay_order',
        'nights',
    ];

    public function flightPath(): BelongsTo
    {
        return $this->belongsTo(FlightPath::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
