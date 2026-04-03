<?php

namespace App\Enums\Inventory;

enum ApprovalAction: string
{
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case RETURNED = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::RETURNED => 'Returned',
        };
    }
}
