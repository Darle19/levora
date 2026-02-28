<?php

namespace App\Models;

use App\Traits\HasLocalizedName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Airline extends Model
{
    use HasFactory, HasLocalizedName;

    protected $fillable = [
        'name',
        'name_en',
        'name_ar',
        'name_ru',
        'code',
        'iata_code',
        'icao_code',
        'logo',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function flights(): HasMany
    {
        return $this->hasMany(Flight::class);
    }

}
