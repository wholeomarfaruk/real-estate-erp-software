<?php

namespace App\Enums\Accounts;

/** Which side of the journal a posting rule line sits on. */
enum PostingLeg: string
{
    case DEBIT  = 'debit';
    case CREDIT = 'credit';

    public function label(): string
    {
        return match ($this) {
            self::DEBIT  => 'Debit',
            self::CREDIT => 'Credit',
        };
    }
}
