<?php

namespace App\Enums\Inventory;

enum PurchaseOrderStatus: string
{
    case DRAFT = 'draft';
    case PENDING_ENGINEER = 'pending_engineer';
    case PENDING_CHAIRMAN = 'pending_chairman';
    case PENDING_ACCOUNTS = 'pending_accounts';
    case APPROVED = 'approved';
    case PARTIALLY_RECEIVED = 'partially_received';
    case RECEIVED = 'received';
    case COMPLETED = 'completed';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING_ENGINEER => 'Pending Engineer',
            self::PENDING_CHAIRMAN => 'Pending Chairman',
            self::PENDING_ACCOUNTS => 'Pending Accounts',
            self::APPROVED => 'Approved',
            self::PARTIALLY_RECEIVED => 'Partially Received',
            self::RECEIVED => 'Received',
            self::COMPLETED => 'Completed',
            self::REJECTED => 'Rejected',
            self::CANCELLED => 'Cancelled',
        };
    }
}
