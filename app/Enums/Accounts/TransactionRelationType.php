<?php

namespace App\Enums\Accounts;

enum TransactionRelationType: string
{
    case PAIR          = 'pair';           // two single-entry txns forming one logical entry
    case ADVANCE_CLEAR = 'advance_clear';  // advance being cleared against invoice/expense
    case REFUND        = 'refund';         // unused advance returned to cash
    case REVERSE       = 'reverse';        // full reversal of a prior transaction

    public function label(): string
    {
        return match ($this) {
            self::PAIR          => 'Pair',
            self::ADVANCE_CLEAR => 'Advance Clear',
            self::REFUND        => 'Refund',
            self::REVERSE       => 'Reverse',
        };
    }
}
