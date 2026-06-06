<?php

namespace App\Enums\Projects;

enum CostType: string
{
    case MATERIAL = 'material';
    case LABOUR   = 'labour';
    case OVERHEAD = 'overhead';
    case INDIRECT = 'indirect';

    public function label(): string
    {
        return match ($this) {
            self::MATERIAL => 'Material',
            self::LABOUR   => 'Labour',
            self::OVERHEAD => 'Overhead',
            self::INDIRECT => 'Indirect',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::MATERIAL => 'bg-[#eaf0f8] text-[#0d2a4a] border border-[#c2dcf3]',
            self::LABOUR   => 'bg-[#e0f2f7] text-[#0e7490] border border-[#a5d8e4]',
            self::OVERHEAD => 'bg-[#fef9e7] text-[#a16207] border border-[#f3e3a8]',
            self::INDIRECT => 'bg-gray-100 text-gray-600 border border-gray-200',
        };
    }
}
