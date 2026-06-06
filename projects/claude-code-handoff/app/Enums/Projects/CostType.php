<?php

namespace App\Enums\Projects;

/** Cost type for estimate line items (BOQ). */
enum CostType: string
{
    case MATERIAL = 'material';
    case LABOUR   = 'labour';
    case OVERHEAD = 'overhead';
    case INDIRECT = 'indirect';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::MATERIAL => '#0d2a4a',
            self::LABOUR   => '#0e7490',
            self::OVERHEAD => '#a16207',
            self::INDIRECT => '#6d28d9',
        };
    }
}
