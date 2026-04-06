<?php

namespace App\Enums\Property;

enum Availability: string
{
    case AVAILABLE = 'available';
    case RESERVED = 'reserved';
    case BOOKED = 'booked';
    case SOLD = 'sold';
    case RENTED = 'rented';
    case INACTIVE = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Available',
            self::RESERVED => 'Reserved',
            self::BOOKED => 'Booked',
            self::SOLD => 'Sold',
            self::RENTED => 'Rented',
            self::INACTIVE => 'Inactive',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::AVAILABLE => 'bg-green-100 text-green-800',
            self::SOLD => 'bg-gray-100 text-gray-800',
            self::RENTED => 'bg-indigo-100 text-indigo-800',
            self::RESERVED, self::BOOKED => 'bg-yellow-100 text-yellow-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
