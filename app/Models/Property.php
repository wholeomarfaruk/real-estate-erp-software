<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'code',
        'property_type',
        'purpose',
        'address',
        'description',
        'total_floors',
        'status',
        'image',
        'documents',
    ];

    protected $casts = [
        'documents' => 'array',
        'total_floors' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function floors(): HasMany
    {
        return $this->hasMany(PropertyFloor::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(PropertyUnit::class);
    }
}
