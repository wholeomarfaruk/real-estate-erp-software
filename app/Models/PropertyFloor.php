<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyFloor extends Model
{
    protected $table = 'property_floors';

    protected $fillable = [
        // legacy
        'floor_name',
        'floor_number',
        'floor_type',
        'status',
        'notes',
        // new
        'property_id',
        'code',
        'label',
        'sort_order',
        'floor_area',
        'remarks',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'floor_area' => 'decimal:2',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(PropertyUnit::class, 'property_floor_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
