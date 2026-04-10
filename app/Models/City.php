<?php

namespace App\Models;

use App\Traits\HasLocalizedName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use HasFactory, HasLocalizedName;

    protected $fillable = [
        'name',
        'name_en',
        'name_uz',
        'name_ru',
        'country_id',
        'code',
        'is_active',
        'is_departure',
        'agent_phone',
        'agent_name',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_departure' => 'boolean',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function resorts(): HasMany
    {
        return $this->hasMany(Resort::class);
    }

    public function airports(): HasMany
    {
        return $this->hasMany(Airport::class);
    }

    public function hotels(): HasMany
    {
        return $this->hasMany(Hotel::class);
    }

    public function additionalServices(): HasMany
    {
        return $this->hasMany(AdditionalService::class);
    }

    public function outboundFlights(): HasMany
    {
        return $this->hasMany(Flight::class, 'origin_city_id');
    }

    public function inboundFlights(): HasMany
    {
        return $this->hasMany(Flight::class, 'destination_city_id');
    }

    public function banners(): HasMany
    {
        return $this->hasMany(Banner::class);
    }
}
