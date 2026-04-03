<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseSettlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'total_fund_released',
        'actual_purchase_amount',
        'returned_cash_amount',
        'due_amount',
        'settled_by',
        'settled_at',
        'remarks',
    ];

    protected $casts = [
        'total_fund_released' => 'decimal:2',
        'actual_purchase_amount' => 'decimal:2',
        'returned_cash_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'settled_at' => 'datetime',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function settler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'settled_by');
    }
}
