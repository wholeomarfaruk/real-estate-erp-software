<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeAdvance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'transaction_id',
        'advance_date',
        'amount',
        'adjusted_amount',
        'remaining_amount',
        'status',
        'notes',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'advance_date' => 'date',
        'amount' => 'decimal:2',
        'adjusted_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(EmployeeAdvanceAdjustment::class)->orderByDesc('adjustment_date')->orderByDesc('id');
    }

    public function recalculateStatus(): void
    {
        $amount = round(max(0, (float) $this->amount), 2);
        $adjusted = round(max(0, min((float) $this->adjusted_amount, $amount)), 2);
        $remaining = round(max(0, $amount - $adjusted), 2);

        $this->amount = $amount;
        $this->adjusted_amount = $adjusted;
        $this->remaining_amount = $remaining;

        if ($remaining <= 0) {
            $this->status = 'cleared';

            return;
        }

        if ($adjusted > 0) {
            $this->status = 'partial';

            return;
        }

        $this->status = 'pending';
    }
}

