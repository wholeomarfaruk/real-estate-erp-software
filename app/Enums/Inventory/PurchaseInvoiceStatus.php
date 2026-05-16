<?php

namespace App\Enums\Inventory;

enum PurchaseInvoiceStatus: string
{
    case PENDING   = 'pending';          // Auto-created from stock receive, awaiting accounts review
    case APPROVED  = 'approved';         // Accounts approved, fully due (no initial payment)
    case PARTIALLY_PAID = 'partially_paid'; // Posted, partial payment made
    case PAID      = 'paid';             // Fully paid (at approval or via payment module)
    case CANCELLED = 'cancelled';        // Cancelled before posting (pending only)

    public function label(): string
    {
        return match ($this) {
            self::PENDING        => 'Pending',
            self::APPROVED       => 'Approved',
            self::PARTIALLY_PAID => 'Partially Paid',
            self::PAID           => 'Paid',
            self::CANCELLED      => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING        => 'yellow',
            self::APPROVED       => 'blue',
            self::PARTIALLY_PAID => 'orange',
            self::PAID           => 'green',
            self::CANCELLED      => 'red',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING        => 'bg-amber-100 text-amber-700',
            self::APPROVED       => 'bg-blue-100 text-blue-700',
            self::PARTIALLY_PAID => 'bg-orange-100 text-orange-700',
            self::PAID           => 'bg-emerald-100 text-emerald-700',
            self::CANCELLED      => 'bg-red-100 text-red-700',
        };
    }

    /** Accounts manager may still edit amounts/accounts while pending. */
    public function isEditable(): bool
    {
        return $this === self::PENDING;
    }

    /** Whether accounting entries have been posted. */
    public function isPosted(): bool
    {
        return in_array($this, [self::APPROVED, self::PARTIALLY_PAID, self::PAID], true);
    }

    public function canBeCancelled(): bool
    {
        return $this === self::PENDING;
    }

    public function canBeDeleted(): bool
    {
        return $this === self::PENDING;
    }
}
