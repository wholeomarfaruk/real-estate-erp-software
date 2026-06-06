<?php

namespace App\Models;

use App\Enums\Accounts\TransactionRelationType;
use App\Enums\Accounts\TransactionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'datetime',
        'type',
        'transaction_category_id',
        'reference_type',
        'reference_id',
        'notes',
        'debit',
        'credit',
        'adjusted_at',
        'adjusted_by',
        'adjusted_transaction_id',
        'created_by',
        'reference_no',
        'name',
        'phone',
        'method',
        'attachments',
        'related_transaction_id',
        'relation_type',
        'external_data',
    ];

    protected $casts = [
        'datetime'    => 'datetime',
        'adjusted_at' => 'datetime',
        'type'        => TransactionType::class,
        'relation_type' => TransactionRelationType::class,
        'debit'       => 'decimal:3',
        'credit'      => 'decimal:3',
        'attachments' => 'array',
        'external_data' => 'array',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function adjustedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function transactionCategory(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class, 'transaction_category_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function collection(): HasOne
    {
        return $this->hasOne(AccountCollection::class, 'transaction_id');
    }

    public function expense(): HasOne
    {
        return $this->hasOne(Expense::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }

    public function relatedTo(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'related_transaction_id');
    }

    public function relatedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'related_transaction_id');
    }

    /** Advance adjustments where this transaction is the source advance. */
    public function advanceAdjustmentsGiven(): HasMany
    {
        return $this->hasMany(AdvanceAdjustment::class, 'advance_transaction_id');
    }

    /** Advance adjustments where this transaction reduced an advance. */
    public function advanceAdjustmentsReceived(): HasMany
    {
        return $this->hasMany(AdvanceAdjustment::class, 'adjust_transaction_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeOfType(Builder $query, TransactionType|string $type): Builder
    {
        $value = $type instanceof TransactionType ? $type->value : $type;
        return $query->where('type', $value);
    }

    public function scopeIncome(Builder $query): Builder
    {
        return $query->where('type', TransactionType::INCOME->value);
    }

    public function scopeExpense(Builder $query): Builder
    {
        return $query->where('type', TransactionType::EXPENSE->value);
    }

    public function scopeAdvance(Builder $query): Builder
    {
        return $query->where('type', TransactionType::ADVANCE->value);
    }

    public function scopeForDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn (Builder $q) => $q->whereDate('datetime', '>=', $from))
            ->when($to,   fn (Builder $q) => $q->whereDate('datetime', '<=', $to));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isAdvance(): bool
    {
        return $this->type === TransactionType::ADVANCE;
    }

    /** Total amount already adjusted against this advance. */
    public function adjustedAmount(): float
    {
        return (float) $this->advanceAdjustmentsGiven()->sum('amount');
    }

    /** Remaining unadjusted advance balance. */
    public function remainingAdvance(): float
    {
        if (! $this->isAdvance()) {
            return 0.0;
        }
        return max(0.0, (float) $this->debit - $this->adjustedAmount());
    }
}
