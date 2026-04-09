<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'type',
        'tourist_id',
        'file_path',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function tourist(): BelongsTo
    {
        return $this->belongsTo(Tourist::class);
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'tourist_voucher' => 'Tourist Voucher',
            'hotel_voucher' => 'Hotel Voucher',
            'eticket' => 'eTicket',
            'insurance' => 'Insurance Policy',
            // Legacy types
            'confirmation' => 'Confirmation',
            'memo' => 'Memo',
            'voucher' => 'Voucher',
            'ticket' => 'Ticket',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    public function getDescription(): string
    {
        return match ($this->type) {
            'hotel_voucher' => 'Hotel',
            'eticket', 'ticket' => $this->tourist ? strtoupper($this->tourist->last_name . ' ' . $this->tourist->first_name) : '',
            'insurance' => $this->tourist ? strtoupper($this->tourist->last_name . ' ' . $this->tourist->first_name) : '',
            default => '',
        };
    }
}
