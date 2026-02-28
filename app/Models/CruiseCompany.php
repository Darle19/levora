<?php

namespace App\Models;

use App\Traits\HasLocalizedDescription;
use App\Traits\HasLocalizedName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CruiseCompany extends Model
{
    use HasLocalizedName, HasLocalizedDescription;

    protected $fillable = [
        'name',
        'name_en',
        'name_ar',
        'name_ru',
        'code',
        'logo',
        'description',
        'description_en',
        'description_ar',
        'description_ru',
        'website',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function ships(): HasMany
    {
        return $this->hasMany(Ship::class);
    }

}
