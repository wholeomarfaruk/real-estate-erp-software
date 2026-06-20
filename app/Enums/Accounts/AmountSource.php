<?php

namespace App\Enums\Accounts;

/**
 * How a posting rule line's amount is derived:
 * - FULL    → the whole event amount (PostingContext::$amount).
 * - CONTEXT → a named amount carried in the context (reserved for future split
 *             rules such as tax/commission legs); not used by the seeded events.
 */
enum AmountSource: string
{
    case FULL    = 'full';
    case CONTEXT = 'context';

    public function label(): string
    {
        return match ($this) {
            self::FULL    => 'Full amount',
            self::CONTEXT => 'Context amount',
        };
    }
}
