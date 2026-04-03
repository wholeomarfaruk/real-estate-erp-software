<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockConsumptionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_consumption_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'remarks',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function stockConsumption(): BelongsTo
    {
        return $this->belongsTo(StockConsumption::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
