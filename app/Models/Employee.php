<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'designation_id',
        'user_id',
        'employee_id',
        'name',
        'phone',
        'email',
        'gender',
        'date_of_birth',
        'joining_date',
        'confirmation_date',
        'exit_date',
        'employment_type',
        'basic_salary',
        'status',
        'has_login',
        'photo_file_id',
        'address',
        'notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'joining_date' => 'date',
        'confirmation_date' => 'date',
        'exit_date' => 'date',
        'basic_salary' => 'decimal:2',
        'has_login' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $employee): void {
            $employee->has_login = (bool) $employee->user_id;
        });
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(File::class, 'photo_file_id');
    }

    public function salaryStructures(): HasMany
    {
        return $this->hasMany(SalaryStructure::class)->orderByDesc('effective_from')->orderByDesc('id');
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class)->orderByDesc('year')->orderByDesc('month')->orderByDesc('id');
    }

    public function advances(): HasMany
    {
        return $this->hasMany(EmployeeAdvance::class)->orderByDesc('advance_date')->orderByDesc('id');
    }
}

