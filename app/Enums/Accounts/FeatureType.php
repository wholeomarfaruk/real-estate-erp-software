<?php

namespace App\Enums\Accounts;

enum FeatureType: string
{
    case PROJECT_EXPENSE = 'project_expense';
    case OFFICE_EXPENSE = 'office_expense';
    case MARKETING_EXPENSE = 'marketing_expense';

    public function label(): string
    {
        return match ($this) {
            self::PROJECT_EXPENSE => 'Project Expense',
            self::OFFICE_EXPENSE => 'Office Expense',
            self::MARKETING_EXPENSE => 'Marketing Expense',
        };
    }
}
