<?php

namespace App\Enums\Property;

enum ScheduleStatus: string
{
    case PENDING = 'pending';
    case PARTIAL = 'partial';
    case PAID    = 'paid';
    case OVERDUE = 'overdue';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::PARTIAL => 'Partial',
            self::PAID    => 'Paid',
            self::OVERDUE => 'Overdue',
        };
    }

    public function color(): array
    {
        return match($this) {
            self::PENDING => ['bg' => '#F7E6C4', 'fg' => '#7A5418'],
            self::PARTIAL => ['bg' => '#D8E4F5', 'fg' => '#1F3D72'],
            self::PAID    => ['bg' => '#D2E7D5', 'fg' => '#1F5A2C'],
            self::OVERDUE => ['bg' => '#F1D3CE', 'fg' => '#7A2A1E'],
        };
    }

    public function isUnpaid(): bool
    {
        return in_array($this, [self::PENDING, self::PARTIAL, self::OVERDUE]);
    }
}
