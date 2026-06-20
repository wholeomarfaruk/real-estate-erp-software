<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertySaleUnit extends Model
{
    protected $table = 'property_sale_units';

    protected $fillable = [
        'property_sale_id',
        'property_id',
        'property_unit_id',
        'sale_amount',
        'discount_amount',
        'tax_amount',
        'net_amount',
        'service_charge',
        'utility_charge',
        'down_payment_percentage',
        'sort_order',
    ];

    protected $casts = [
        'sale_amount'             => 'decimal:2',
        'discount_amount'         => 'decimal:2',
        'tax_amount'              => 'decimal:2',
        'net_amount'              => 'decimal:2',
        'service_charge'          => 'decimal:2',
        'utility_charge'          => 'decimal:2',
        'down_payment_percentage' => 'decimal:2',
        'sort_order'              => 'integer',
    ];

    public function propertySale(): BelongsTo
    {
        return $this->belongsTo(PropertySale::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function propertyUnit(): BelongsTo
    {
        return $this->belongsTo(PropertyUnit::class);
    }
}
