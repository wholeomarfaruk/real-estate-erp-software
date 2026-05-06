<?php

namespace App\Models;

use App\Enums\Inventory\StockReceiveStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockReceive extends Model
{
    use HasFactory;

    protected $fillable = [
        'receive_no',
        'store_receiver_no',
        'receive_date',
        'supplier_id',
        'purchase_order_id',
        'supplier_voucher',
        'store_id',
        'remarks',
        'images',
        'status',
        'created_by',
        'posted_by',
        'posted_at',
    ];

    protected $casts = [
        'receive_date' => 'date',
        'posted_at' => 'datetime',
        'status' => StockReceiveStatus::class,
        'images' => 'array',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockReceiveItem::class);
    }

    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class, 'stock_receive_id');
    }

    public function supplierBills(): HasMany
    {
        return $this->hasMany(SupplierBill::class, 'stock_receive_id');
    }

    public function supplierReturns(): HasMany
    {
        return $this->hasMany(SupplierReturn::class, 'stock_receive_id');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', StockReceiveStatus::DRAFT->value);
    }

    public function scopePosted(Builder $query): Builder
    {
        return $query->where('status', StockReceiveStatus::POSTED->value);
    }

    public function settlementCompleted(): bool
    {
        return (bool) $this->purchaseOrder?->settlement?->settled_at;
    }

    public function canAdjustPostedReceive(): bool
    {
        return $this->status === StockReceiveStatus::POSTED && ! $this->settlementCompleted();
    }

    public function canEditReceive(): bool
    {
        return match ($this->status) {
            StockReceiveStatus::DRAFT => true,
            StockReceiveStatus::POSTED => $this->canAdjustPostedReceive(),
            default => false,
        };
    }
}
