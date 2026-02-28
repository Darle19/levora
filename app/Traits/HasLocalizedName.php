<?php

namespace App\Traits;

trait HasLocalizedName
{
    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->{"name_$locale"} ?? $this->name_en ?? $this->attributes['name'] ?? '';
    }
}
