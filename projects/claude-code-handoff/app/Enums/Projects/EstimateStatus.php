<?php

namespace App\Enums\Projects;

enum EstimateStatus: string
{
    case DRAFT     = 'draft';
    case SUBMITTED = 'submitted';
    case APPROVED  = 'approved';
    case REJECTED  = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT     => 'Draft',
            self::SUBMITTED => 'Submitted',
            self::APPROVED  => 'Approved',
            self::REJECTED  => 'Rejected',
        };
    }

    /** Approved estimates are locked — edits require a new version. */
    public function isLocked(): bool
    {
        return $this === self::APPROVED;
    }
}
