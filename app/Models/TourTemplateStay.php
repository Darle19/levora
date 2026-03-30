<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourTemplateStay extends Model
{
    protected $fillable = [
        'tour_template_id',
        'city_id',
        'stay_order',
        'nights',
    ];

    protected function casts(): array
    {
        return [
            'stay_order' => 'integer',
            'nights' => 'integer',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(TourTemplate::class, 'tour_template_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
