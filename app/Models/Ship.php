<?php

namespace App\Models;

use App\Traits\HasLocalizedDescription;
use App\Traits\HasLocalizedName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ship extends Model
{
    use HasLocalizedName, HasLocalizedDescription;

    protected $fillable = [
        'name',
        'name_en',
        'name_ar',
        'name_ru',
        'cruise_company_id',
        'code',
        'capacity',
        'year_built',
        'description',
        'description_en',
        'description_ar',
        'description_ru',
        'images',
        'amenities',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'capacity' => 'integer',
            'year_built' => 'integer',
            'images' => 'array',
            'amenities' => 'array',
        ];
    }

    public function cruiseCompany(): BelongsTo
    {
        return $this->belongsTo(CruiseCompany::class);
    }

    public function cruiseRoutes(): HasMany
    {
        return $this->hasMany(CruiseRoute::class);
    }

}
