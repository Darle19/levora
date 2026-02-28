<?php

namespace App\Models;

use App\Traits\HasLocalizedDescription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promotion extends Model
{
    use HasLocalizedDescription;

    protected $fillable = [
        'title',
        'title_en',
        'title_ar',
        'title_ru',
        'description',
        'description_en',
        'description_ar',
        'description_ru',
        'currency_id',
        'discount_type',
        'discount_value',
        'code',
        'start_date',
        'end_date',
        'max_uses',
        'used_count',
        'conditions',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'discount_value' => 'decimal:2',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'conditions' => 'array',
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function getTitleAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->{"title_$locale"} ?? $this->title_en ?? $this->attributes['title'];
    }

}
