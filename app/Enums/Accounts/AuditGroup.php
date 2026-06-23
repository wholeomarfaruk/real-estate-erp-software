<?php

namespace App\Enums\Accounts;

enum AuditGroup: string
{
    case CASH = 'cash';
    case BANKING = 'banking';
    case DIGITAL = 'digital';
    case ADJUSTMENT = 'adjustment';
    case INTERNAL = 'internal';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::BANKING => 'Banking',
            self::DIGITAL => 'Digital/MFS',
            self::ADJUSTMENT => 'Adjustment',
            self::INTERNAL => 'Internal',
        };
    }

    public function requiresAudit(): bool
    {
        return $this !== self::INTERNAL;
    }

    public function requiresReconciliation(): bool
    {
        return in_array($this, [self::CASH, self::BANKING, self::DIGITAL]);
    }

    public function isAdjustment(): bool
    {
        return $this === self::ADJUSTMENT;
    }
}
