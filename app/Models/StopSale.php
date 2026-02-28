<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StopSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_id',
        'start_date',
        'end_date',
        'reason',
        'reason_en',
        'reason_ar',
        'reason_ru',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function getReasonAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->{"reason_$locale"} ?? $this->reason_en ?? $this->attributes['reason'] ?? null;
    }
}
