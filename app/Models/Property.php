<?php

namespace App\Models;

use App\Models\Concerns\HasFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use SoftDeletes, HasFiles;

    protected $fillable = [
        // legacy columns (kept for existing code)
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
        // new real-estate catalog columns
        'type',
        'total_area',
        'land_size',
        'engineer_id',
        'registered_at',
        'remarks',
        'property_images',
    ];

    protected $casts = [
        'documents'       => 'array',
        'property_images' => 'array',
        'type'            => 'array',
        'total_floors'    => 'integer',
        'total_area'      => 'decimal:2',
        'land_size'       => 'decimal:2',
        'registered_at'   => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function engineer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'engineer_id');
    }

    public function floors(): HasMany
    {
        return $this->hasMany(PropertyFloor::class)->orderBy('sort_order')->orderBy('id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(PropertyUnit::class);
    }

    // ── scopes ───────────────────────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
