<?php

namespace App\Enums\Accounts;

enum TransactionType: string
{
       case INCOME = 'income';
    case EXPENSE = 'expense';
    case ADVANCE = 'advance';
    case TRANSFER = 'transfer';
    case ADJUSTMENT = 'adjustment';
    case OPENING_BALANCE = 'opening_balance';


    public function label(): string
    {
        return match ($this) {
            self::INCOME => 'Income',
            self::EXPENSE => 'Expense',
            self::ADVANCE => 'Advance',
            self::TRANSFER => 'Transfer',
            self::ADJUSTMENT => 'Adjustment',
            self::OPENING_BALANCE => 'Opening Balance',
        };
    }
}
