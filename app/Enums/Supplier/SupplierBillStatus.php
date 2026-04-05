<?php

namespace App\Enums\Supplier;

enum SupplierBillStatus: string
{
    case DRAFT = 'draft';
    case OPEN = 'open';
    case PARTIAL = 'partial';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::OPEN => 'Open',
            self::PARTIAL => 'Partial',
            self::PAID => 'Paid',
            self::OVERDUE => 'Overdue',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-zinc-100 text-zinc-700',
            self::OPEN => 'bg-blue-100 text-blue-700',
            self::PARTIAL => 'bg-amber-100 text-amber-700',
            self::PAID => 'bg-green-100 text-green-700',
            self::OVERDUE => 'bg-rose-100 text-rose-700',
            self::CANCELLED => 'bg-gray-100 text-gray-700',
        };
    }

    public function isPending(): bool
    {
        return in_array($this, [self::OPEN, self::PARTIAL, self::OVERDUE], true);
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::DRAFT, self::OPEN], true);
    }
}
