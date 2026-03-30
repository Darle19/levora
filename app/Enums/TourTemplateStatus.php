<?php

// File: app/Enums/TourTemplateStatus.php

namespace App\Enums;

enum TourTemplateStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';
    case FlightsLocked = 'flights_locked';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Active',
            self::Archived => 'Archived',
            self::FlightsLocked => 'Flights Locked',
        };
    }
}
