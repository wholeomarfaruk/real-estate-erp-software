<?php

namespace App\Models;

use App\Enums\Accounts\PurchasePayableStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchasePayable extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'supplier_id',
        'transaction_id',
        'payable_amount',
        'paid_amount',
        'due_amount',
        'status',
    ];

    protected $casts = [
        'payable_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'status' => PurchasePayableStatus::class,
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function recalculateDueAndStatus(): void
    {
        $payable = round(max(0, (float) $this->payable_amount), 2);
        $paid = round(max(0, min((float) $this->paid_amount, $payable)), 2);
        $due = round(max(0, $payable - $paid), 2);

        $this->payable_amount = $payable;
        $this->paid_amount = $paid;
        $this->due_amount = $due;

        if ($due <= 0) {
            $this->status = PurchasePayableStatus::PAID->value;

            return;
        }

        if ($paid > 0) {
            $this->status = PurchasePayableStatus::PARTIAL->value;

            return;
        }

        $this->status = PurchasePayableStatus::UNPAID->value;
    }
}
