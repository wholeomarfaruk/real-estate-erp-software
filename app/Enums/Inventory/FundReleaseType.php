<?php

namespace App\Enums\Inventory;

enum FundReleaseType: string
{
    case EMPLOYEE_ADVANCE = 'employee_advance';
    case SUPPLIER_ADVANCE = 'supplier_advance';

    public function label(): string
    {
        return match ($this) {
            self::EMPLOYEE_ADVANCE => 'Employee Advance',
            self::SUPPLIER_ADVANCE => 'Supplier Advance',
        };
    }

    public function drDescription(): string
    {
        return match ($this) {
            self::EMPLOYEE_ADVANCE => 'Employee advance disbursed',
            self::SUPPLIER_ADVANCE => 'Supplier advance disbursed',
        };
    }
}
