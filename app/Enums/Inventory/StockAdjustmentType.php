<?php

namespace App\Enums\Inventory;

enum StockAdjustmentType: string
{
    case IN = 'in';
    case OUT = 'out';

    public function label(): string
    {
        return match ($this) {
            self::IN => 'Adjustment In',
            self::OUT => 'Adjustment Out',
        };
    }
}
