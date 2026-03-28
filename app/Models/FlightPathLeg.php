<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlightPathLeg extends Model
{
    protected $fillable = [
        'flight_path_id',
        'flight_id',
        'leg_order',
        'direction',
    ];

    public function flightPath(): BelongsTo
    {
        return $this->belongsTo(FlightPath::class);
    }

    public function flight(): BelongsTo
    {
        return $this->belongsTo(Flight::class);
    }
}
