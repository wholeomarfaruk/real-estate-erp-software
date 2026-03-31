<?php

namespace App\Enums\Project;

enum Type: string
{
    case RESIDENTIAL = 'residential';
    case COMMERCIAL = 'commercial';
    case LUXURY = 'luxury';
    case CLASSIC = 'classic';
    public function label(): string
    {
        return match ($this) {
            self::RESIDENTIAL => 'Residential',
            self::COMMERCIAL => 'Commercial',
            self::LUXURY => 'Luxury',
            self::CLASSIC => 'Classic',
        };
    }
}
