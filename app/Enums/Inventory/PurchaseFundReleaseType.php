<?php

namespace App\Enums\Inventory;

enum PurchaseFundReleaseType: string
{
      case CASH = 'cash';
    case CREDIT = 'credit';
    case CHEQUE = 'cheque';


    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::CREDIT => 'Credit',
            self::CHEQUE => 'Cheque',

        };
    }
}
