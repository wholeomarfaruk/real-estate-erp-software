<?php

namespace App\Models;

use App\Enums\Accounts\PaymentRequestSourceType;
use App\Enums\Accounts\TransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BankingPaymentRequest extends Model
{
    protected $fillable = [
        'request_no',
        'source_type',
        'sourceable_type',
        'sourceable_id',
        'transaction_category_id',
        'transaction_id',
        'amount',
        'payment_date',
        'description',
        'bank_account_id',
        'account_id',
        'debit_account_id',
        'debit_amount',
        'credit_account_id',
        'credit_amount',
        'reference_no',
        'name',
        'phone',
        'method',
        'status',
        'notes',
        'rejection_reason',
        'requested_by',
        'approved_by',
        'approved_at',
        'released_by',
        'released_at',
        'completed_by',
        'completed_at',
        'rejected_by',
        'rejected_at',
        'external_data',
    ];

    protected $casts = [
        'amount'       => 'decimal:3',
        'payment_date' => 'date',
        'approved_at'  => 'datetime',
        'released_at'  => 'datetime',
        'completed_at' => 'datetime',
        'rejected_at'  => 'datetime',
        'external_data' => 'array',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    /** Chart-of-accounts money account the payment is/was made from. */
    public function account(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Account::class, 'account_id');
    }

    public function debitAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Account::class, 'debit_account_id');
    }

    public function creditAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Account::class, 'credit_account_id');
    }

    public function sourceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function transactionCategory(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function sourceTypeEnum(): TransactionType|PaymentRequestSourceType|null
    {
        return TransactionType::tryFrom($this->source_type)
            ?? PaymentRequestSourceType::tryFrom($this->source_type);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'pending'   => 'bg-amber-50 text-amber-700 border-amber-200',
            'approved'  => 'bg-blue-50 text-blue-700 border-blue-200',
            'released'  => 'bg-violet-50 text-violet-700 border-violet-200',
            'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'rejected'  => 'bg-rose-50 text-rose-700 border-rose-200',
            default     => 'bg-gray-100 text-gray-600 border-gray-200',
        };
    }

    public static function generateRequestNo(): string
    {
        $last = static::latest('id')->value('request_no');
        $seq  = $last ? ((int) substr($last, -5)) + 1 : 1;
        return 'BPR-' . now()->format('ymd') . '-' . str_pad($seq, 5, '0', STR_PAD_LEFT);
    }

    public function getPaymentAccount(): ?Account
    {
        if ($this->account_id) {
            return $this->account;
        }

        if ($this->bank_account_id) {
            $bankAccount = $this->bankAccount;
            if ($bankAccount?->account_id) {
                return Account::find($bankAccount->account_id);
            }
        }

        return null;
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed' && $this->transaction_id !== null;
    }

    public function canBeCompleted(): bool
    {
        if ($this->status !== 'released') {
            return false;
        }

        if ((float) $this->amount <= 0) {
            return false;
        }

        return $this->getPaymentAccount() !== null;
    }
}
