<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyFloor extends Model
{
    protected $table = 'property_floors';

    protected $fillable = [
        'property_id',
        'floor_name',
        'floor_number',
        'floor_type',
        'status',
        'notes',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(PropertyUnit::class, 'property_floor_id');
    }
}
