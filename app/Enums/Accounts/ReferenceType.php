<?php

namespace App\Enums\Accounts;

enum ReferenceType: string
{
    case ADVANCE_SALARY = \App\Models\EmployeeAdvance::class;

    public function label(): string
    {
        return match ($this) {
            self::ADVANCE_SALARY => 'Advance Salary',
        };
    }

}

