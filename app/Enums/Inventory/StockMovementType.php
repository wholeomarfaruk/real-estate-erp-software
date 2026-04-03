<?php

namespace App\Enums\Inventory;

enum StockMovementType: string
{
    case PURCHASE = 'purchase';
    case RECEIVE = 'receive';
    case TRANSFER_IN = 'transfer_in';
    case TRANSFER_OUT = 'transfer_out';
    case CONSUMPTION = 'consumption';
    case ADJUSTMENT_IN = 'adjustment_in';
    case ADJUSTMENT_OUT = 'adjustment_out';
    case RETURN = 'return';

    public function label(): string
    {
        return match ($this) {
            self::PURCHASE => 'Purchase',
            self::RECEIVE => 'Receive',
            self::TRANSFER_IN => 'Transfer In',
            self::TRANSFER_OUT => 'Transfer Out',
            self::CONSUMPTION => 'Consumption',
            self::ADJUSTMENT_IN => 'Adjustment In',
            self::ADJUSTMENT_OUT => 'Adjustment Out',
            self::RETURN => 'Return',
        };
    }
}
