<?php

namespace App\Models;

use App\Enums\Accounts\EntryMethod;
use App\Enums\Inventory\FundReleaseType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseFund extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'release_type',
        'advance_type',
        'advance_account_id',
        'payment_account_id',
        'transaction_id',
        'payment_id',
        'amount',
        'released_by',
        'release_date',
        'remarks',
        'payto',
        'receiver_type',
        'receiver_id',
    ];

    protected $casts = [
        'release_type' => EntryMethod::class,
        'advance_type' => FundReleaseType::class,
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

    public function advanceAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'advance_account_id');
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payment_account_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function receiver()
    {
        return $this->morphTo();
    }
}
