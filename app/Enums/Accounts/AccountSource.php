<?php

namespace App\Enums\Accounts;

/**
 * Where a posting rule line gets its account:
 * - FIXED   → a configured account (posting_rules.account_id).
 * - RUNTIME → an account supplied at post time (e.g. the payment account the
 *             user selects), keyed by posting_rules.runtime_slot.
 */
enum AccountSource: string
{
    case FIXED   = 'fixed';
    case RUNTIME = 'runtime';

    public function label(): string
    {
        return match ($this) {
            self::FIXED   => 'Fixed account',
            self::RUNTIME => 'User-selected at runtime',
        };
    }
}
