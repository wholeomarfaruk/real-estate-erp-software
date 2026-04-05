<?php

namespace App\Enums\Supplier;

enum SupplierBillReferenceType: string
{
    case MANUAL = 'manual';
    case LINKED_PURCHASE_ORDER = 'linked_purchase_order';
    case LINKED_STOCK_RECEIVE = 'linked_stock_receive';

    public function label(): string
    {
        return match ($this) {
            self::MANUAL => 'Manual',
            self::LINKED_PURCHASE_ORDER => 'Linked Purchase Order',
            self::LINKED_STOCK_RECEIVE => 'Linked Stock Receive',
        };
    }
}
