<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelCommissionTier extends Model
{
    protected $fillable = ['min_nights', 'max_nights', 'commission'];

    protected function casts(): array
    {
        return [
            'min_nights' => 'integer',
            'max_nights' => 'integer',
            'commission' => 'decimal:2',
        ];
    }

    /**
     * Get commission amount for a given number of nights.
     */
    public static function getForNights(int $nights): float
    {
        $tier = static::where('min_nights', '<=', $nights)
            ->where('max_nights', '>=', $nights)
            ->first();

        return $tier ? (float) $tier->commission : 0;
    }
}
