<?php

namespace App\Models;

use App\Models\Concerns\HasFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyUnit extends Model
{
    use SoftDeletes, HasFiles;

    protected $table = 'property_units';

    protected $fillable = [
        // legacy
        'unit_number',
        'unit_name',
        'unit_type',
        'purpose',
        'down_payment_percentage',
        'deposit_amount',
        'size_sqft',
        'sell_price',
        'rent_amount',
        'bedrooms',
        'bathrooms',
        'balcony',
        'facing',
        'availability_status',
        'notes',
        // new canonical columns
        'property_id',
        'property_floor_id',
        'code',
        'type',
        'status',
        'area',
        'price',
        'service_charge',
        'sort_order',
        'extra_data',
    ];

    protected $casts = [
        'down_payment_percentage' => 'decimal:2',
        'deposit_amount'         => 'decimal:2',
        'size_sqft'              => 'decimal:2',
        'sell_price'     => 'decimal:2',
        'rent_amount'    => 'decimal:2',
        'area'           => 'decimal:2',
        'price'          => 'decimal:3',
        'service_charge' => 'decimal:3',
        'sort_order'     => 'integer',
        'extra_data'     => 'array',
    ];

    // ── convenient getters that bridge old/new column names ─────────────────

    public function getEffectiveCodeAttribute(): string
    {
        return $this->code ?? $this->unit_number ?? (string) $this->id;
    }

    public function getEffectiveTypeAttribute(): string
    {
        return $this->type ?? $this->unit_type ?? 'flat';
    }

    public function getEffectiveStatusAttribute(): string
    {
        return $this->status ?? $this->availability_status ?? 'available';
    }

    public function getEffectivePriceAttribute(): float
    {
        return (float) ($this->price ?: $this->sell_price ?? 0);
    }

    public function getEffectiveAreaAttribute(): float
    {
        return (float) ($this->area ?: $this->size_sqft ?? 0);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(PropertyFloor::class, 'property_floor_id');
    }
}
