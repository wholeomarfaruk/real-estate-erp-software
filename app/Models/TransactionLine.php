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
        'notes',
    ];

    protected $casts = [
        'debit'  => 'decimal:3',
        'credit' => 'decimal:3',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $line): void {
            $debit  = (float) $line->debit;
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

    // ── Parent-transaction proxies ────────────────────────────────────────────
    // Report readers that previously iterated Transaction rows now iterate the
    // per-account ledger lines. These accessors expose the parent transaction's
    // header fields so existing reader/helper code keeps working unchanged.

    public function getDatetimeAttribute(): mixed
    {
        return $this->transaction?->datetime;
    }

    public function getTypeAttribute(): mixed
    {
        return $this->transaction?->type;
    }

    public function getNameAttribute(): mixed
    {
        return $this->transaction?->name;
    }

    public function getReferenceNoAttribute(): mixed
    {
        return $this->transaction?->reference_no;
    }

    public function getReferenceTypeAttribute(): mixed
    {
        return $this->transaction?->reference_type;
    }

    public function getReferenceIdAttribute(): mixed
    {
        return $this->transaction?->reference_id;
    }

    public function getPaymentAttribute(): mixed
    {
        return $this->transaction?->payment;
    }

    public function getCollectionAttribute(): mixed
    {
        return $this->transaction?->collection;
    }

    public function getExpenseAttribute(): mixed
    {
        return $this->transaction?->expense;
    }

    public function getReferenceAttribute(): mixed
    {
        return $this->transaction?->reference;
    }

    public function getMethodAttribute(): mixed
    {
        return $this->transaction?->method;
    }

    /**
     * The header transaction_category_id column was removed; categories are no
     * longer attached to transactions. Kept as a null proxy so legacy readers that
     * reference ->transactionCategory degrade gracefully instead of erroring.
     */
    public function getTransactionCategoryAttribute(): mixed
    {
        return null;
    }
}
