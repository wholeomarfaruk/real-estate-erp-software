<?php

namespace App\Enums\Property;

enum Type: string
{
    case RESIDENTIAL = 'residential';
    case COMMERCIAL = 'commercial';
    case MIXED_USE = 'mixed_use';
    case OTHER = 'other';
    case LUXURY = 'luxury';
    case CLASSIC = 'classic';

    public function label(): string
    {
        return match ($this) {
            self::RESIDENTIAL => 'Residential',
            self::COMMERCIAL => 'Commercial',
            self::MIXED_USE => 'Mixed Use',
            self::OTHER => 'Other',
            self::LUXURY => 'Luxury',
            self::CLASSIC => 'Classic',
        };
    }
}
