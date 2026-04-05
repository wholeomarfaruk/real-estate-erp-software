<?php

namespace App\Models;

use App\Enums\Supplier\SupplierReturnReferenceType;
use App\Enums\Supplier\SupplierReturnStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierReturn extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'return_no',
        'return_date',
        'reference_type',
        'reference_id',
        'supplier_bill_id',
        'stock_receive_id',
        'purchase_order_id',
        'reason',
        'notes',
        'subtotal',
        'total_amount',
        'status',
        'approved_by',
        'approved_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'return_date' => 'date',
        'reference_type' => SupplierReturnReferenceType::class,
        'subtotal' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'status' => SupplierReturnStatus::class,
        'approved_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function supplierBill(): BelongsTo
    {
        return $this->belongsTo(SupplierBill::class, 'supplier_bill_id');
    }

    public function stockReceive(): BelongsTo
    {
        return $this->belongsTo(StockReceive::class, 'stock_receive_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierReturnItem::class)->orderBy('id');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', SupplierReturnStatus::APPROVED->value);
    }

    public function canEdit(): bool
    {
        return $this->status === SupplierReturnStatus::DRAFT;
    }

    public function canApprove(): bool
    {
        return $this->status === SupplierReturnStatus::DRAFT;
    }

    public function canCancel(): bool
    {
        return $this->status !== SupplierReturnStatus::CANCELLED;
    }

    public function getReferenceTypeLabelAttribute(): string
    {
        return $this->reference_type?->label() ?? 'N/A';
    }

    public function getReferenceNoAttribute(): string
    {
        return match ($this->reference_type) {
            SupplierReturnReferenceType::SUPPLIER_BILL => $this->supplierBill?->bill_no ?: 'N/A',
            SupplierReturnReferenceType::STOCK_RECEIVE => $this->stockReceive?->receive_no ?: 'N/A',
            SupplierReturnReferenceType::PURCHASE_ORDER => $this->purchaseOrder?->po_no ?: 'N/A',
            default => $this->return_no,
        };
    }
}
