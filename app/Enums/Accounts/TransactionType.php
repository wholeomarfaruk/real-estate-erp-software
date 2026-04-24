<?php

namespace App\Enums\Accounts;

enum TransactionType: string
{
    case PAYMENT = 'payment';
    case COLLECTION = 'collection';
    case EXPENSE = 'expense';
    case JOURNAL = 'journal';

    public function label(): string
    {
        return match ($this) {
            self::PAYMENT => 'Payment',
            self::COLLECTION => 'Collection',
            self::EXPENSE => 'Expense',
            self::JOURNAL => 'Journal',
        };
    }
}
