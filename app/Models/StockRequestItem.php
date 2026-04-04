<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_request_id',
        'product_id',
        'quantity',
        'approved_quantity',
        'fulfilled_quantity',
        'remarks',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'approved_quantity' => 'decimal:3',
        'fulfilled_quantity' => 'decimal:3',
    ];

    public function stockRequest(): BelongsTo
    {
        return $this->belongsTo(StockRequest::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
