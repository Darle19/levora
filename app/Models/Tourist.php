<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tourist extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'title',
        'first_name',
        'last_name',
        'middle_name',
        'birth_date',
        'birth_country',
        'gender',
        'nationality',
        'document_type',
        'passport_series',
        'passport_number',
        'passport_expiry',
        'passport_issued',
        'passport_issued_by',
    ];

    protected $hidden = [
        'passport_number',
        'passport_series',
        'passport_expiry',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'passport_expiry' => 'date',
            'passport_issued' => 'date',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
