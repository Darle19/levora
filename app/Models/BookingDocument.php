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
            'confirmation' => __('messages.doc_confirmation'),
            'memo' => __('messages.doc_memo'),
            'voucher' => __('messages.doc_voucher'),
            'ticket' => __('messages.doc_ticket'),
            'insurance' => __('messages.doc_insurance'),
            default => ucfirst($this->type),
        };
    }

    public function getDescription(): string
    {
        return match ($this->type) {
            'voucher' => $this->booking->bookable?->hotel?->name ?? '',
            'ticket' => $this->tourist ? strtoupper($this->tourist->last_name . ' ' . $this->tourist->first_name) : '',
            'insurance' => $this->tourist ? strtoupper($this->tourist->last_name . ' ' . $this->tourist->first_name) : '',
            default => '',
        };
    }
}
