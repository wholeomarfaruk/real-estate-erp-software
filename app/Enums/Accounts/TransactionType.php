<?php

namespace App\Enums\Accounts;

enum TransactionType: string
{
    case INCOME           = 'income';
    case EXPENSE          = 'expense';
    case ADVANCE          = 'advance';
    case TRANSFER         = 'transfer';
    case ADJUSTMENT       = 'adjustment';
    case OPENING_BALANCE  = 'opening_balance';
    case PURCHASE         = 'purchase';

    public function label(): string
    {
        return match ($this) {
            self::INCOME           => 'Income',
            self::EXPENSE          => 'Expense',
            self::ADVANCE          => 'Advance',
            self::TRANSFER         => 'Transfer',
            self::ADJUSTMENT       => 'Adjustment',
            self::OPENING_BALANCE  => 'Opening Balance',
            self::PURCHASE         => 'Purchase Bill',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::INCOME           => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::EXPENSE          => 'bg-rose-50 text-rose-700 border-rose-200',
            self::ADVANCE          => 'bg-violet-50 text-violet-700 border-violet-200',
            self::TRANSFER         => 'bg-blue-50 text-blue-700 border-blue-200',
            self::ADJUSTMENT       => 'bg-amber-50 text-amber-700 border-amber-200',
            self::OPENING_BALANCE  => 'bg-gray-100 text-gray-600 border-gray-200',
            self::PURCHASE         => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        };
    }
}
