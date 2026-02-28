<?php

namespace App\Models;

use App\Traits\HasLocalizedDescription;
use App\Traits\HasLocalizedName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resort extends Model
{
    use HasFactory, HasLocalizedName, HasLocalizedDescription;

    protected $fillable = [
        'name',
        'name_en',
        'name_ar',
        'name_ru',
        'country_id',
        'city_id',
        'code',
        'description',
        'description_en',
        'description_ar',
        'description_ru',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function hotels(): HasMany
    {
        return $this->hasMany(Hotel::class);
    }

}
