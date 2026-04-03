<?php

namespace App\Enums\Inventory;

enum ApprovalStage: string
{
    case ENGINEER = 'engineer';
    case CHAIRMAN = 'chairman';
    case ACCOUNTS = 'accounts';

    public function label(): string
    {
        return match ($this) {
            self::ENGINEER => 'Engineer',
            self::CHAIRMAN => 'Chairman',
            self::ACCOUNTS => 'Accounts',
        };
    }
}
