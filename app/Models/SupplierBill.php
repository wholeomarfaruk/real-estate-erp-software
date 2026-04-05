<?php

namespace App\Models;

use App\Enums\Supplier\SupplierBillReferenceType;
use App\Enums\Supplier\SupplierBillStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierBill extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'bill_no',
        'bill_date',
        'due_date',
        'reference_type',
        'reference_id',
        'purchase_order_id',
        'stock_receive_id',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'other_charge',
        'total_amount',
        'paid_amount',
        'due_amount',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bill_date' => 'date',
        'due_date' => 'date',
        'reference_type' => SupplierBillReferenceType::class,
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'other_charge' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'status' => SupplierBillStatus::class,
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function stockReceive(): BelongsTo
    {
        return $this->belongsTo(StockReceive::class, 'stock_receive_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierBillItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(SupplierPaymentAllocation::class, 'supplier_bill_id');
    }

    public function supplierReturns(): HasMany
    {
        return $this->hasMany(SupplierReturn::class, 'supplier_bill_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query
            ->whereIn('status', [
                SupplierBillStatus::OPEN->value,
                SupplierBillStatus::PARTIAL->value,
                SupplierBillStatus::OVERDUE->value,
            ])
            ->where('due_amount', '>', 0);
    }

    public function canEdit(): bool
    {
        return in_array($this->status, [SupplierBillStatus::DRAFT, SupplierBillStatus::OPEN], true);
    }

    public function canCancel(): bool
    {
        return ! in_array($this->status, [SupplierBillStatus::PAID, SupplierBillStatus::CANCELLED], true);
    }

    public function resolveStatus(bool $preserveDraft = false): SupplierBillStatus
    {
        if ($this->status === SupplierBillStatus::CANCELLED) {
            return SupplierBillStatus::CANCELLED;
        }

        if ($preserveDraft) {
            return SupplierBillStatus::DRAFT;
        }

        $dueAmount = round(max(0, (float) $this->due_amount), 2);
        $paidAmount = round(max(0, (float) $this->paid_amount), 2);

        if ($dueAmount <= 0.0) {
            return SupplierBillStatus::PAID;
        }

        if ($this->due_date && $this->due_date->isPast() && $dueAmount > 0) {
            return SupplierBillStatus::OVERDUE;
        }

        if ($paidAmount > 0) {
            return SupplierBillStatus::PARTIAL;
        }

        return SupplierBillStatus::OPEN;
    }

    public function syncAmountsAndStatus(bool $preserveDraft = false): void
    {
        $subtotal = round(max(0, (float) $this->subtotal), 2);
        $discount = round(max(0, (float) $this->discount_amount), 2);
        $tax = round(max(0, (float) $this->tax_amount), 2);
        $otherCharge = round(max(0, (float) $this->other_charge), 2);
        $paidAmount = round(max(0, (float) $this->paid_amount), 2);

        $totalAmount = round(max(0, $subtotal - $discount + $tax + $otherCharge), 2);
        $dueAmount = round(max(0, $totalAmount - $paidAmount), 2);

        $this->subtotal = $subtotal;
        $this->discount_amount = $discount;
        $this->tax_amount = $tax;
        $this->other_charge = $otherCharge;
        $this->total_amount = $totalAmount;
        $this->paid_amount = $paidAmount;
        $this->due_amount = $dueAmount;
        $this->status = $this->resolveStatus($preserveDraft)->value;
    }

    public static function syncOverdueStatuses(): void
    {
        static::query()
            ->whereIn('status', [SupplierBillStatus::OPEN->value, SupplierBillStatus::PARTIAL->value])
            ->whereDate('due_date', '<', now()->toDateString())
            ->where('due_amount', '>', 0)
            ->update(['status' => SupplierBillStatus::OVERDUE->value]);

        static::query()
            ->where('status', SupplierBillStatus::OVERDUE->value)
            ->where(function (Builder $query): void {
                $query->whereNull('due_date')
                    ->orWhereDate('due_date', '>=', now()->toDateString())
                    ->orWhere('due_amount', '<=', 0);
            })
            ->chunkById(100, function ($bills): void {
                foreach ($bills as $bill) {
                    $bill->syncAmountsAndStatus();
                    $bill->save();
                }
            });
    }

    public function getReferenceTypeLabelAttribute(): string
    {
        return $this->reference_type?->label() ?? 'N/A';
    }

    public function getReferenceNoAttribute(): string
    {
        if ($this->reference_type === SupplierBillReferenceType::LINKED_PURCHASE_ORDER) {
            return $this->purchaseOrder?->po_no ?: 'N/A';
        }

        if ($this->reference_type === SupplierBillReferenceType::LINKED_STOCK_RECEIVE) {
            return $this->stockReceive?->receive_no ?: 'N/A';
        }

        return $this->bill_no;
    }
}
