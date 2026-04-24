<?php

namespace App\Models;

use App\Enums\Inventory\PurchaseMode;
use App\Enums\Inventory\PurchaseOrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_no',
        'order_date',
        'requested_by',
        'store_id',
        'supplier_id',
        'purchase_mode',
        'fund_request_amount',
        'approved_amount',
        'actual_purchase_amount',
        'returned_amount',
        'due_amount',
        'status',
        'engineer_approved_by',
        'engineer_approved_at',
        'chairman_approved_by',
        'chairman_approved_at',
        'accounts_approved_by',
        'accounts_approved_at',
        'remarks',
    ];

    protected $casts = [
        'order_date' => 'date',
        'purchase_mode' => PurchaseMode::class,
        'fund_request_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'actual_purchase_amount' => 'decimal:2',
        'returned_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'status' => PurchaseOrderStatus::class,
        'engineer_approved_at' => 'datetime',
        'chairman_approved_at' => 'datetime',
        'accounts_approved_at' => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function engineerApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'engineer_approved_by');
    }

    public function chairmanApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chairman_approved_by');
    }

    public function accountsApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accounts_approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(PurchaseOrderApproval::class);
    }

    public function funds(): HasMany
    {
        return $this->hasMany(PurchaseFund::class);
    }

    public function settlement(): HasOne
    {
        return $this->hasOne(PurchaseSettlement::class);
    }

    public function stockReceives(): HasMany
    {
        return $this->hasMany(StockReceive::class, 'purchase_order_id');
    }

    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class, 'purchase_order_id');
    }

    public function supplierBills(): HasMany
    {
        return $this->hasMany(SupplierBill::class, 'purchase_order_id');
    }

    public function supplierReturns(): HasMany
    {
        return $this->hasMany(SupplierReturn::class, 'purchase_order_id');
    }

    public function purchasePayable(): HasOne
    {
        return $this->hasOne(PurchasePayable::class, 'purchase_order_id');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', PurchaseOrderStatus::DRAFT->value);
    }

    public function scopeApprovedForReceive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            PurchaseOrderStatus::APPROVED->value,
            PurchaseOrderStatus::PARTIALLY_RECEIVED->value,
        ]);
    }
}
