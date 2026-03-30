<?php

// File: app/Models/TourTemplateStay.php

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
        'check_in_date',
        'check_out_date',
    ];

    protected function casts(): array
    {
        return [
            'stay_order' => 'integer',
            'nights' => 'integer',
            'check_in_date' => 'date',
            'check_out_date' => 'date',
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
