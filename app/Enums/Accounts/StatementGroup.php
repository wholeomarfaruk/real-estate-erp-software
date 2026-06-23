<?php

namespace App\Enums\Accounts;

enum StatementGroup: string
{
    case CASH_DEPOSIT = 'cash_deposit';
    case ONLINE_DEPOSIT = 'online_deposit';
    case CASH_WITHDRAW = 'cash_withdraw';
    case ONLINE_TRANSFER = 'online_transfer';
    case IGNORE = 'ignore';

    public function label(): string
    {
        return match ($this) {
            self::CASH_DEPOSIT => 'Cash Deposit',
            self::ONLINE_DEPOSIT => 'Online Deposit',
            self::CASH_WITHDRAW => 'Cash Withdraw',
            self::ONLINE_TRANSFER => 'Online Transfer',
            self::IGNORE => 'Ignore',
        };
    }

    public function isDeposit(): bool
    {
        return in_array($this, [self::CASH_DEPOSIT, self::ONLINE_DEPOSIT]);
    }

    public function isWithdrawal(): bool
    {
        return in_array($this, [self::CASH_WITHDRAW, self::ONLINE_TRANSFER]);
    }

    public function isCash(): bool
    {
        return in_array($this, [self::CASH_DEPOSIT, self::CASH_WITHDRAW]);
    }

    public function isOnline(): bool
    {
        return in_array($this, [self::ONLINE_DEPOSIT, self::ONLINE_TRANSFER]);
    }
}
