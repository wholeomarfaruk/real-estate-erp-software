<?php

namespace App\Enums\Supplier;

enum SupplierLedgerTransactionType: string
{
    case OPENING_BALANCE = 'opening_balance';
    case BILL = 'bill';
    case PAYMENT = 'payment';
    case RETURN_TXN = 'return';
    case ADJUSTMENT = 'adjustment';
    case ADVANCE_ADJUSTMENT = 'advance_adjustment';

    public function label(): string
    {
        return match ($this) {
            self::OPENING_BALANCE => 'Opening Balance',
            self::BILL => 'Bill',
            self::PAYMENT => 'Payment',
            self::RETURN_TXN => 'Return',
            self::ADJUSTMENT => 'Adjustment',
            self::ADVANCE_ADJUSTMENT => 'Advance Adjustment',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::OPENING_BALANCE => 'bg-zinc-100 text-zinc-700',
            self::BILL => 'bg-indigo-100 text-indigo-700',
            self::PAYMENT => 'bg-emerald-100 text-emerald-700',
            self::RETURN_TXN => 'bg-sky-100 text-sky-700',
            self::ADJUSTMENT => 'bg-amber-100 text-amber-700',
            self::ADVANCE_ADJUSTMENT => 'bg-fuchsia-100 text-fuchsia-700',
        };
    }
}
