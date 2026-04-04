<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockRequestTransferLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_request_id',
        'transfer_transaction_id',
    ];

    public function stockRequest(): BelongsTo
    {
        return $this->belongsTo(StockRequest::class);
    }

    public function transferTransaction(): BelongsTo
    {
        return $this->belongsTo(TransferTransaction::class);
    }
}
