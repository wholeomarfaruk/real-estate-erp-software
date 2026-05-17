<?php

namespace App\Models;

use App\Enums\Property\PaymentCategory;
use App\Enums\Property\ScheduleStatus;
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
        'due_date'          => 'date',
        'amount'            => 'decimal:2',
        'paid_amount'       => 'decimal:2',
        'due_amount'        => 'decimal:2',
        'is_auto_generated' => 'boolean',
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
            'installment'      => 'Installment ' . str_pad($this->sequence_no ?? 1, 2, '0', STR_PAD_LEFT),
            'monthly_rent'     => 'Monthly Rent #' . str_pad($this->sequence_no ?? 1, 2, '0', STR_PAD_LEFT),
            'security_deposit' => 'Security Deposit',
            'extra_charge'     => 'Extra Charge' . ($this->remarks ? ': ' . $this->remarks : ''),
            'manual_charge'    => 'Manual Charge' . ($this->remarks ? ': ' . $this->remarks : ''),
            default            => ucwords(str_replace('_', ' ', $this->payment_category)),
        };
    }

    public function displayStatus(): string
    {
        if ($this->status !== 'paid' && $this->due_date->isPast()) {
            return 'overdue';
        }
        return $this->status;
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'paid' && $this->due_date->isPast();
    }

    public function isUnpaid(): bool
    {
        return in_array($this->status, ['pending', 'partial', 'overdue']);
    }

    public function statusColor(): array
    {
        return match($this->displayStatus()) {
            'paid'    => ['bg' => '#D2E7D5', 'fg' => '#1F5A2C'],
            'partial' => ['bg' => '#D8E4F5', 'fg' => '#1F3D72'],
            'overdue' => ['bg' => '#F1D3CE', 'fg' => '#7A2A1E'],
            default   => ['bg' => '#F7E6C4', 'fg' => '#7A5418'],
        };
    }
    public function paymentTransactions(){
        //morph
        return $this->morphMany(Transaction::class, 'reference');
    }
}
