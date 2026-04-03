<?php

namespace App\Enums\Inventory;

enum TransferStatus: string
{
    case DRAFT = 'draft';
    case REQUESTED = 'requested';
    case APPROVED = 'approved';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::REQUESTED => 'Requested',
            self::APPROVED => 'Approved',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }
}
