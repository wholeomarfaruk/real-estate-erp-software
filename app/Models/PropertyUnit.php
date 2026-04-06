<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyUnit extends Model
{
    protected $table = 'property_units';

    protected $fillable = [
        'property_id',
        'property_floor_id',
        'unit_number',
        'unit_name',
        'unit_type',
        'purpose',
        'size_sqft',
        'sell_price',
        'rent_amount',
        'bedrooms',
        'bathrooms',
        'balcony',
        'facing',
        'availability_status',
        'notes',
    ];

    protected $casts = [
        'size_sqft' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'rent_amount' => 'decimal:2',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(PropertyFloor::class, 'property_floor_id');
    }
}
