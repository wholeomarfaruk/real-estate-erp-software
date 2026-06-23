<?php

namespace App\Enums\Accounts;

enum CashFlowGroup: string
{
    case INFLOW = 'inflow';
    case OUTFLOW = 'outflow';
    case NON_CASH = 'non_cash';

    public function label(): string
    {
        return match ($this) {
            self::INFLOW => 'Inflow',
            self::OUTFLOW => 'Outflow',
            self::NON_CASH => 'Non-Cash',
        };
    }

    public function affectsCashFlow(): bool
    {
        return $this !== self::NON_CASH;
    }

    public function isInflow(): bool
    {
        return $this === self::INFLOW;
    }

    public function isOutflow(): bool
    {
        return $this === self::OUTFLOW;
    }

    public function isNonCash(): bool
    {
        return $this === self::NON_CASH;
    }
}
