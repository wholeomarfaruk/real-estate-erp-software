<?php

namespace App\Enums\Inventory;

enum StockMovementDirection: string
{
    case IN = 'in';
    case OUT = 'out';

    public function label(): string
    {
        return match ($this) {
            self::IN => 'In',
            self::OUT => 'Out',
        };
    }
}
