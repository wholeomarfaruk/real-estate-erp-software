<?php

namespace App\Enums\Supplier;

enum SupplierReturnStatus: string
{
    case DRAFT = 'draft';
    case APPROVED = 'approved';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::APPROVED => 'Approved',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-zinc-100 text-zinc-700',
            self::APPROVED => 'bg-emerald-100 text-emerald-700',
            self::CANCELLED => 'bg-rose-100 text-rose-700',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::DRAFT;
    }
}
