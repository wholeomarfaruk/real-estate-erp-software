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

    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT     => 'bg-gray-100 text-gray-600 border border-gray-200',
            self::SUBMITTED => 'bg-amber-50 text-amber-700 border border-amber-200',
            self::APPROVED  => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
            self::REJECTED  => 'bg-red-50 text-red-700 border border-red-200',
        };
    }

    public function isLocked(): bool
    {
        return $this === self::APPROVED;
    }
}
