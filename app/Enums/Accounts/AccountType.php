<?php

namespace App\Enums\Accounts;

enum AccountType: string
{
    case ASSET = 'asset';
    case LIABILITY = 'liability';
    case INCOME = 'income';
    case EXPENSE = 'expense';
    case EQUITY = 'equity';

    public function label(): string
    {
        return match ($this) {
            self::ASSET => 'Asset',
            self::LIABILITY => 'Liability',
            self::INCOME => 'Income',
            self::EXPENSE => 'Expense',
            self::EQUITY => 'Equity',
        };
    }
}
