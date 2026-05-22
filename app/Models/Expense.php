<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Expense extends Model
{
    protected $fillable = [
        'expense_no',
        'title',
        'date',
        'amount',
        'status',
        'expense_account_id',
        'payment_account_id',
        'bank_account_id',
        'transaction_category_id',
        'transaction_id',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:3',
        'date'   => 'date',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payment_account_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function transactionCategory(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bankingRequest(): MorphOne
    {
        return $this->morphOne(BankingPaymentRequest::class, 'sourceable');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'draft'   => 'bg-gray-100 text-gray-600 border border-gray-200',
            'pending' => 'bg-amber-50 text-amber-700 border border-amber-200',
            'posted'  => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
            default   => 'bg-gray-100 text-gray-500 border border-gray-200',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft'   => 'Draft',
            'pending' => 'Pending',
            'posted'  => 'Posted',
            default   => ucfirst($this->status ?? ''),
        };
    }
}
