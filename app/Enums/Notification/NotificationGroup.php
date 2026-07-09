<?php

namespace App\Enums\Notification;

enum NotificationGroup: string
{
    case FINANCIAL = 'financial';
    case HR        = 'hr';
    case MARKETING = 'marketing';
    case SYSTEM    = 'system';

    public function label(): string
    {
        return match ($this) {
            self::FINANCIAL => 'Financial',
            self::HR        => 'HR',
            self::MARKETING => 'Marketing',
            self::SYSTEM    => 'System',
        };
    }
}
