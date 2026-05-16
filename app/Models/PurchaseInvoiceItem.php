<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseInvoiceItem extends Model
{
    protected $fillable = [
        'purchase_invoice_id',
        'product_id',
        'stock_receive_item_id',
        'purchase_order_item_id',
        'qty',
        'unit_price',
        'discount_amount',
        'total_amount',
        'remarks',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'unit_price' => 'decimal:3',
        'discount_amount' => 'decimal:3',
        'total_amount' => 'decimal:3',
    ];

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockReceiveItem(): BelongsTo
    {
        return $this->belongsTo(StockReceiveItem::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }
}
