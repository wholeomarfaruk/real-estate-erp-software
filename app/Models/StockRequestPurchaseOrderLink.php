<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockRequestPurchaseOrderLink extends Model
{
    protected $fillable = [
        'stock_request_id',
        'purchase_order_id',
        'product_id',
        'linked_quantity',
        'remarks',
    ];

    protected $casts = [
        'linked_quantity' => 'decimal:3',
    ];

    public function stockRequest(): BelongsTo
    {
        return $this->belongsTo(StockRequest::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
