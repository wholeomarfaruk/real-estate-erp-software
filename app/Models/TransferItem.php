<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_transaction_id',
        'product_id',
        'quantity',
        'received_quantity',
        'unit_price',
        'total_price',
        'remarks',
        'checked_by_sender_at',
        'checked_by_receiver_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'received_quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'checked_by_sender_at' => 'datetime',
        'checked_by_receiver_at' => 'datetime',
    ];

    public function transferTransaction(): BelongsTo
    {
        return $this->belongsTo(TransferTransaction::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
