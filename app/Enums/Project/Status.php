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
    public function badge(): string
{
    return match ($this) {
        self::COMPLETED => 'bg-green-100 text-green-800',
        self::ONGOING => 'bg-blue-100 text-blue-800',
        self::ON_HOLD => 'bg-yellow-100 text-yellow-800',
        default => 'bg-gray-100 text-gray-800',
    };
}
}
