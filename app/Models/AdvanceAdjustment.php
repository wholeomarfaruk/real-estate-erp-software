<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvanceAdjustment extends Model
{
    protected $table = 'advance_adjustments';

    protected $fillable = [
        'advance_transaction_id',
        'adjust_transaction_id',
        'amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:3',
    ];

    public function advanceTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'advance_transaction_id');
    }

    public function adjustTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'adjust_transaction_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
