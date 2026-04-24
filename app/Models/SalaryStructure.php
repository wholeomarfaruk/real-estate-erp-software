<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'effective_from',
        'basic_salary',
        'house_rent',
        'medical_allowance',
        'transport_allowance',
        'food_allowance',
        'other_allowance',
        'gross_salary',
        'status',
        'notes',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'basic_salary' => 'decimal:2',
        'house_rent' => 'decimal:2',
        'medical_allowance' => 'decimal:2',
        'transport_allowance' => 'decimal:2',
        'food_allowance' => 'decimal:2',
        'other_allowance' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'status' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $structure): void {
            $structure->gross_salary = $structure->calculateGrossSalary();
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function calculateGrossSalary(): float
    {
        return round(
            (float) $this->basic_salary
            + (float) $this->house_rent
            + (float) $this->medical_allowance
            + (float) $this->transport_allowance
            + (float) $this->food_allowance
            + (float) $this->other_allowance,
            2
        );
    }
}

