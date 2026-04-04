<?php

namespace App\Models;

use App\Enums\Inventory\PurchaseReturnStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_no',
        'return_date',
        'supplier_id',
        'store_id',
        'purchase_order_id',
        'stock_receive_id',
        'reason',
        'remarks',
        'status',
        'created_by',
        'posted_by',
        'posted_at',
    ];

    protected $casts = [
        'return_date' => 'date',
        'posted_at' => 'datetime',
        'status' => PurchaseReturnStatus::class,
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
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

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', PurchaseReturnStatus::DRAFT->value);
    }

    public function scopePosted(Builder $query): Builder
    {
        return $query->where('status', PurchaseReturnStatus::POSTED->value);
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', PurchaseReturnStatus::CANCELLED->value);
    }
}
