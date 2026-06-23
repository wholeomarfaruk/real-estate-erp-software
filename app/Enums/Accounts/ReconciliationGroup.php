<?php

namespace App\Enums\Accounts;

enum ReconciliationGroup: string
{
    case BANK_RECONCILIATION = 'bank_reconciliation';
    case MFS_RECONCILIATION = 'mfs_reconciliation';
    case NOT_APPLICABLE = 'not_applicable';

    public function label(): string
    {
        return match ($this) {
            self::BANK_RECONCILIATION => 'Bank Reconciliation',
            self::MFS_RECONCILIATION => 'MFS Reconciliation',
            self::NOT_APPLICABLE => 'Not Applicable',
        };
    }

    public function isReconcilable(): bool
    {
        return $this !== self::NOT_APPLICABLE;
    }

    public function isBankReconciliation(): bool
    {
        return $this === self::BANK_RECONCILIATION;
    }

    public function isMFSReconciliation(): bool
    {
        return $this === self::MFS_RECONCILIATION;
    }
}
