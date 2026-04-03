<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'quantity',
        'estimated_unit_price',
        'estimated_total_price',
        'approved_quantity',
        'approved_unit_price',
        'approved_total_price',
        'remarks',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'estimated_unit_price' => 'decimal:2',
        'estimated_total_price' => 'decimal:2',
        'approved_quantity' => 'decimal:3',
        'approved_unit_price' => 'decimal:2',
        'approved_total_price' => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockReceiveItems(): HasMany
    {
        return $this->hasMany(StockReceiveItem::class, 'purchase_order_item_id');
    }
}
