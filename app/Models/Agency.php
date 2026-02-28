<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agency extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'legal_name',
        'legal_address',
        'phone',
        'mobile',
        'email',
        'website',
        'director',
        'inn',
        'bank_account',
        'bank_name',
        'mfo',
        'is_active',
        'notes',
    ];

    protected $hidden = [
        'bank_account',
        'bank_name',
        'mfo',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
