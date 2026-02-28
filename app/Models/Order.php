<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'agency_id',
        'user_id',
        'currency_id',
        'status',
        'total_price',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_price' => 'decimal:2',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getPaymentPercentage(): float
    {
        if ($this->total_price <= 0) {
            return 0;
        }

        $totalPaid = $this->payments()
            ->where('status', 'completed')
            ->sum('amount');

        return min(round(($totalPaid / $this->total_price) * 100, 2), 100);
    }

    public function hasDocuments(): bool
    {
        return $this->bookings()->whereHas('documents')->exists();
    }

    public function isFullyPaid(): bool
    {
        return $this->getPaymentPercentage() >= 100;
    }
}
