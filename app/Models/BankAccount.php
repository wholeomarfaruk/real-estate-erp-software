<?php

namespace App\Models;

use App\Enums\Accounts\BankAccountType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    protected $table = 'bank_accounts';

    protected $fillable = [
        'type',
        'bank_name',
        'code',
        'ac_number',
        'branch',
        'holder_name',
        'route_code',
        'swift_code',
        'address',
        'note',
        'status',
        'account_id',
        'phone',
        'email',
        'files',
    ];

    protected $casts = [
        'files' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(BankingPaymentRequest::class);
    }

    public function getMaskedAcNumberAttribute(): string
    {
        if (!$this->ac_number || $this->ac_number === '—') {
            return '—';
        }
        $clean = preg_replace('/\s+/', '', $this->ac_number);
        if (strlen($clean) <= 8) {
            return $clean;
        }
        return substr($clean, 0, 4) . ' •••• ' . substr($clean, -4);
    }

    public function getLogoInitialAttribute(): string
    {
        return strtoupper(mb_substr($this->bank_name ?? '?', 0, 1));
    }

    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'bank'   => '#0d2a4a',
            'cash'   => '#b45309',
            'mfs'    => '#be185d',
            'wallet' => '#6d28d9',
            default  => '#374151',
        };
    }

    public function getTypeEnumAttribute(): ?BankAccountType
    {
        if (!$this->type) {
            return null;
        }
        return BankAccountType::tryFrom($this->type);
    }
}
