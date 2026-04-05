<?php

namespace App\Enums\Supplier;

enum SupplierPaymentStatus: string
{
    case DRAFT = 'draft';
    case POSTED = 'posted';
    case PARTIAL_ALLOCATED = 'partial_allocated';
    case FULLY_ALLOCATED = 'fully_allocated';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::POSTED => 'Posted',
            self::PARTIAL_ALLOCATED => 'Partial Allocated',
            self::FULLY_ALLOCATED => 'Fully Allocated',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-zinc-100 text-zinc-700',
            self::POSTED => 'bg-blue-100 text-blue-700',
            self::PARTIAL_ALLOCATED => 'bg-amber-100 text-amber-700',
            self::FULLY_ALLOCATED => 'bg-green-100 text-green-700',
            self::CANCELLED => 'bg-gray-100 text-gray-700',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::DRAFT;
    }
}
