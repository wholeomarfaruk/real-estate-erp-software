<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'account_id',
        'debit',
        'credit',
        'description',
    ];

    protected $casts = [
        'debit' => 'decimal:3',
        'credit' => 'decimal:3',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $line): void {
            $debit = (float) $line->debit;
            $credit = (float) $line->credit;

            if ($debit > 0 && $credit > 0) {
                throw new \DomainException('A transaction line cannot have both debit and credit amounts.');
            }

            if ($debit <= 0 && $credit <= 0) {
                throw new \DomainException('A transaction line must have debit or credit amount greater than zero.');
            }
        });
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
