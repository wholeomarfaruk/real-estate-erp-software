<?php

namespace App\Enums\Project;

enum Status: string
{
    case UPCOMING = 'upcoming';
    case ONGOING = 'ongoing';
    case ON_HOLD = 'on_hold';
    case COMPLETED = 'completed';
    public function label(): string
    {
        return match ($this) {
            self::UPCOMING => 'Upcoming',
            self::ONGOING => 'Ongoing',
            self::ON_HOLD => 'On Hold',
            self::COMPLETED => 'Completed',
        };
    }
}
