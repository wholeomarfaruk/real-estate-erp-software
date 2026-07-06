<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ClientOtpVerification extends Model
{
    protected $fillable = [
        'verification_id',
        'user_id',
        'channel',
        'destination',
        'otp_hash',
        'attempts',
        'expires_at',
        'verified_at',
        'last_sent_at',
    ];

    protected $casts = [
        'expires_at'   => 'datetime',
        'verified_at'  => 'datetime',
        'last_sent_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ClientOtpVerification $otp) {
            $otp->verification_id ??= (string) Str::uuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }
}
