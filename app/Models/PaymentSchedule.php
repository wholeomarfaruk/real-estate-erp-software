<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentSchedule extends Model
{
    protected $fillable = [
        'property_sale_id',
        'payment_category',
        'sequence_no',
        'due_date',
        'amount',
        'paid_amount',
        'due_amount',
        'status',
        'is_auto_generated',
        'remarks',
    ];

    protected $casts = [
        'due_date'         => 'date',
        'amount'           => 'decimal:2',
        'paid_amount'      => 'decimal:2',
        'due_amount'       => 'decimal:2',
        'is_auto_generated'=> 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function propertySale(): BelongsTo
    {
        return $this->belongsTo(PropertySale::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function label(): string
    {
        return match($this->payment_category) {
            'down_payment'     => 'Down Payment',
            'installment'      => 'Installment ' . str_pad($this->sequence_no, 2, '0', STR_PAD_LEFT),
            'monthly_rent'     => 'Monthly Rent #' . $this->sequence_no,
            'security_deposit' => 'Security Deposit',
            default            => ucwords(str_replace('_', ' ', $this->payment_category)),
        };
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_date->isPast();
    }
}
