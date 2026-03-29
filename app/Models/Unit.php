<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Unit extends Model
{
    protected $fillable = [
        'project_id',
        'floor_id',
        'unit_number',
        'unit_type',
        'size_sqft',
        'price',
        'facing',
        'bedrooms',
        'bathrooms',
        'balcony',
        'availability_status',
        'notes',
    ];

    protected $casts = [
        'size_sqft' => 'decimal:2',
        'price' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }
}
