<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourPrice extends Model
{
    protected $fillable = [
        'tour_id',
        'room_type_id',
        'price_adult',
        'price_child',
        'price_infant',
        'currency_id',
        'availability',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'price_adult' => 'decimal:2',
            'price_child' => 'decimal:2',
            'price_infant' => 'decimal:2',
            'availability' => 'integer',
        ];
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
