<?php

namespace App\Enums\Accounts;

enum TransactionSubCategory:string
{
    case SALE = 'sale';
    case RENT = 'rent';
    case PURCHASE = 'purchase';

    public function label(): string
    {
        return match ($this) {
            self::SALE => 'Sale',
            self::RENT => 'Rent',
            self::PURCHASE => 'Purchase',
        };
    }
    public static function referenceModels(): array
    {
        return [
            self::SALE => PropertySale::class,
            self::RENT => PropertySale::class,
            self::PURCHASE => PropertySale::class,
        ];
    }
}
