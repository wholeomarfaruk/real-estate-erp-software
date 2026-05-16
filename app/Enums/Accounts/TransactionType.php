<?php

namespace App\Enums\Accounts;

enum TransactionType: string
{
    case PAYMENT = 'payment';
    case COLLECTION = 'collection';
    case EXPENSE = 'expense';
    case JOURNAL = 'journal';
    case PURCHASE_INVOICE = 'purchase_invoice';
    case FUND_RELEASE     = 'fund_release';

    public function label(): string
    {
        return match ($this) {
            self::PAYMENT         => 'Payment',
            self::COLLECTION      => 'Collection',
            self::EXPENSE         => 'Expense',
            self::JOURNAL         => 'Journal',
            self::PURCHASE_INVOICE => 'Purchase Invoice',
            self::FUND_RELEASE    => 'Fund Release',
        };
    }
}
