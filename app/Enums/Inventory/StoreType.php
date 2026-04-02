<?php

namespace App\Enums\Inventory;

enum StoreType: string
{
    case OFFICE = 'office';
    case PROJECT = 'project';

    public function label(): string
    {
        return match ($this) {
            self::OFFICE => 'Office',
            self::PROJECT => 'Project',
        };
    }
}
