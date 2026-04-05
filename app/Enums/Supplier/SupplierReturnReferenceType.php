<?php

namespace App\Enums\Supplier;

enum SupplierReturnReferenceType: string
{
    case MANUAL = 'manual';
    case SUPPLIER_BILL = 'supplier_bill';
    case STOCK_RECEIVE = 'stock_receive';
    case PURCHASE_ORDER = 'purchase_order';

    public function label(): string
    {
        return match ($this) {
            self::MANUAL => 'Manual',
            self::SUPPLIER_BILL => 'Supplier Bill',
            self::STOCK_RECEIVE => 'Stock Receive',
            self::PURCHASE_ORDER => 'Purchase Order',
        };
    }
}
