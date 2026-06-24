<?php

namespace App\Models;

use App\Enums\Accounts\EntryMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAdvanceAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_advance_id',
        'payroll_id',
        'amount',
        'adjustment_date',
        'method',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'adjustment_date' => 'date',
        'method' => EntryMethod::class,
    ];

    public function employeeAdvance(): BelongsTo
    {
        return $this->belongsTo(EmployeeAdvance::class);
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }
}

