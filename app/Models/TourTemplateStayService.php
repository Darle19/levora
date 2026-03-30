<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourTemplateStayService extends Model
{
    protected $fillable = [
        'tour_template_stay_id',
        'additional_service_id',
        'price_cents',
        'currency',
        'is_mandatory',
    ];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'is_mandatory' => 'boolean',
        ];
    }

    public function stay(): BelongsTo
    {
        return $this->belongsTo(TourTemplateStay::class, 'tour_template_stay_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(AdditionalService::class, 'additional_service_id');
    }

    public function priceFormatted(): string
    {
        return number_format($this->price_cents / 100, 2) . ' ' . $this->currency;
    }
}
