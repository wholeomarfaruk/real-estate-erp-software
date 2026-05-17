<?php

namespace App\Enums\Property;

enum ScheduleType: string
{
    case DAILY   = 'daily';
    case WEEKLY  = 'weekly';
    case MONTHLY = 'monthly';
    case YEARLY  = 'yearly';

    public function label(): string
    {
        return match($this) {
            self::DAILY   => 'Daily',
            self::WEEKLY  => 'Weekly',
            self::MONTHLY => 'Monthly',
            self::YEARLY  => 'Yearly',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
