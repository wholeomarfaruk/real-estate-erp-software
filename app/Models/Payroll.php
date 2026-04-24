<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'salary_structure_id',
        'transaction_id',
        'month',
        'year',
        'payroll_date',
        'basic_salary',
        'allowance_total',
        'bonus_total',
        'deduction_total',
        'gross_salary',
        'net_salary',
        'payment_status',
        'payment_date',
        'notes',
        'generated_by',
        'approved_by',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'payroll_date' => 'date',
        'basic_salary' => 'decimal:2',
        'allowance_total' => 'decimal:2',
        'bonus_total' => 'decimal:2',
        'deduction_total' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function salaryStructure(): BelongsTo
    {
        return $this->belongsTo(SalaryStructure::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function advanceAdjustments(): HasMany
    {
        return $this->hasMany(EmployeeAdvanceAdjustment::class)->orderBy('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PayrollPayment::class)->orderByDesc('payment_date')->orderByDesc('id');
    }

    public function getTotalPaidAttribute(): float
    {
        if (array_key_exists('total_paid', $this->attributes)) {
            return round((float) $this->attributes['total_paid'], 2);
        }

        return round((float) $this->payments()->sum('amount'), 2);
    }

    public function getDueAmountAttribute(): float
    {
        return round(max(0, (float) $this->net_salary - $this->total_paid), 2);
    }
}

