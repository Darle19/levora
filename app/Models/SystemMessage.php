<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemMessage extends Model
{
    protected $fillable = [
        'title',
        'title_en',
        'title_ar',
        'title_ru',
        'message',
        'message_en',
        'message_ar',
        'message_ru',
        'type',
        'priority',
        'created_by',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTitleAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->{"title_$locale"} ?? $this->title_en ?? $this->attributes['title'];
    }

    public function getMessageAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->{"message_$locale"} ?? $this->message_en ?? $this->attributes['message'];
    }
}
