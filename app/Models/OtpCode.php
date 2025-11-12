<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class OtpCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'telephone',
        'otp_code',
        'expires_at',
        'used',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean',
    ];

    /**
     * Scope for active (unused and not expired) OTP codes.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereRaw('used = false')
                    ->where('expires_at', '>', now());
    }

    /**
     * Check if OTP is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
