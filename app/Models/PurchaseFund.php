<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class PurchaseFund extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'transaction_id',
        'amount',
        'released_by',
        'release_date',
        'remarks',
        'payto',
        'receiver_type',
        'receiver_id',
        'status',
        'transaction_category_id',
        'bank_account_id',
        'payment_account_id',
        'method',
        'reference_no',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'release_date' => 'date',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function releaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function transactionCategory(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    /** Chart-of-accounts money account the advance is paid from (the Cr leg). */
    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payment_account_id');
    }

    public function receiver()
    {
        return $this->morphTo();
    }

    public function bankingRequest(): MorphOne
    {
        return $this->morphOne(BankingPaymentRequest::class, 'sourceable');
    }
}
