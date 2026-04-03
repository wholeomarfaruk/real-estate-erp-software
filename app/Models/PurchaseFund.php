<?php

namespace App\Models;

use App\Enums\Inventory\PurchaseFundReleaseType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseFund extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'release_type',
        'amount',
        'released_by',
        'received_by',
        'release_date',
        'remarks',
    ];

    protected $casts = [
        'release_type' => PurchaseFundReleaseType::class,
        'amount' => 'decimal:2',
        'release_date' => 'date',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function releaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
