<?php

namespace App\Models;

use App\Traits\HasLocalizedDescription;
use App\Traits\HasLocalizedName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CruiseRoute extends Model
{
    use HasLocalizedName, HasLocalizedDescription;

    protected $fillable = [
        'name',
        'name_en',
        'name_ar',
        'name_ru',
        'ship_id',
        'from_port_id',
        'to_port_id',
        'currency_id',
        'departure_date',
        'arrival_date',
        'duration_days',
        'duration_nights',
        'price_adult',
        'price_child',
        'price_infant',
        'description',
        'description_en',
        'description_ar',
        'description_ru',
        'itinerary',
        'images',
        'is_active',
        'max_capacity',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'departure_date' => 'date',
            'arrival_date' => 'date',
            'duration_days' => 'integer',
            'duration_nights' => 'integer',
            'price_adult' => 'decimal:2',
            'price_child' => 'decimal:2',
            'price_infant' => 'decimal:2',
            'itinerary' => 'array',
            'images' => 'array',
            'max_capacity' => 'integer',
        ];
    }

    public function ship(): BelongsTo
    {
        return $this->belongsTo(Ship::class);
    }

    public function fromPort(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'from_port_id');
    }

    public function toPort(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'to_port_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

}
