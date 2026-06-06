<?php

namespace App\Enums\Projects;

enum WorkPhase: string
{
    case FOUNDATION = 'foundation';
    case STRUCTURE  = 'structure';
    case BRICK_WORK = 'brick_work';
    case PLASTER    = 'plaster';
    case ELECTRICAL = 'electrical';
    case PLUMBING   = 'plumbing';
    case FINISHING  = 'finishing';
    case OTHER      = 'other';

    public function label(): string
    {
        return match ($this) {
            self::BRICK_WORK => 'Brick Work',
            default          => ucfirst(str_replace('_', ' ', $this->value)),
        };
    }

    public static function options(): array
    {
        return array_map(fn($case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
