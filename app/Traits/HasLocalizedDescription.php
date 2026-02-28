<?php

namespace App\Traits;

trait HasLocalizedDescription
{
    public function getDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->{"description_$locale"} ?? $this->description_en ?? $this->attributes['description'] ?? null;
    }
}
