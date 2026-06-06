<?php

namespace App\Enums\Projects;

enum ProjectStatus: string
{
    case UPCOMING  = 'upcoming';
    case RUNNING   = 'running';
    case ON_HOLD   = 'on_hold';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::UPCOMING  => 'Upcoming',
            self::RUNNING   => 'Running',
            self::ON_HOLD   => 'On Hold',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    /** UI colour tag */
    public function colorTag(): string
    {
        return match ($this) {
            self::UPCOMING  => 'warning',
            self::RUNNING   => 'info',
            self::COMPLETED => 'success',
            self::ON_HOLD   => 'muted',
            self::CANCELLED => 'danger',
        };
    }
}
