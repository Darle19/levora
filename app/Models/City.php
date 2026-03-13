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

    public function resorts(): HasMany
    {
        return $this->hasMany(Resort::class);
    }

    public function airports(): HasMany
    {
        return $this->hasMany(Airport::class);
    }

}
