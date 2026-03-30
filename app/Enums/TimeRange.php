<?php

// File: app/Enums/TimeRange.php

namespace App\Enums;

enum TimeRange: string
{
    case Any = 'any';
    case Morning = 'morning';       // 00:00–11:59
    case Afternoon = 'afternoon';   // 12:00–17:59
    case Evening = 'evening';       // 18:00–23:59

    public function matchesHour(int $hour): bool
    {
        return match ($this) {
            self::Any => true,
            self::Morning => $hour < 12,
            self::Afternoon => $hour >= 12 && $hour < 18,
            self::Evening => $hour >= 18,
        };
    }
}
