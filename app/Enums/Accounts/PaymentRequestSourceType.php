<?php

namespace App\Enums\Accounts;

enum PaymentRequestSourceType: string
{
    case EXPENSE           = 'expense';
    case SUPPLIER          = 'supplier';
    case PAYROLL           = 'payroll';
    case EMPLOYEE_ADVANCE  = 'employee_advance';
    case LANDOWNER         = 'landowner';
    case OTHER             = 'other';

    public function label(): string
    {
        return match($this) {
            self::EXPENSE          => 'Expense',
            self::SUPPLIER         => 'Supplier',
            self::PAYROLL          => 'Payroll',
            self::EMPLOYEE_ADVANCE => 'Employee Advance',
            self::LANDOWNER        => 'Land Owner',
            self::OTHER            => 'Other',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::EXPENSE          => 'bg-rose-50 text-rose-700 border-rose-200',
            self::SUPPLIER         => 'bg-orange-50 text-orange-700 border-orange-200',
            self::PAYROLL          => 'bg-blue-50 text-blue-700 border-blue-200',
            self::EMPLOYEE_ADVANCE => 'bg-purple-50 text-purple-700 border-purple-200',
            self::LANDOWNER        => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::OTHER            => 'bg-gray-100 text-gray-600 border-gray-200',
        };
    }
}
