<?php

namespace App\Enums\Accounts;

enum ReportGroup: string
{
    case RECEIPT = 'receipt';
    case PAYMENT = 'payment';
    case NEUTRAL = 'neutral';

    public function label(): string
    {
        return match ($this) {
            self::RECEIPT => 'Receipt',
            self::PAYMENT => 'Payment',
            self::NEUTRAL => 'Neutral',
        };
    }
}
