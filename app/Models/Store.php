<?php

namespace App\Models;

use App\Enums\Inventory\StoreType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Store extends Model
{
    protected $fillable = [
        'name',
        'code',
        'type',
        'project_id',
        'address',
        'description',
        'status',
    ];

    protected $casts = [
        'type' => StoreType::class,
        'status' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    public function scopeOffice(Builder $query): Builder
    {
        return $query->where('type', StoreType::OFFICE->value);
    }

    public function scopeProject(Builder $query): Builder
    {
        return $query->where('type', StoreType::PROJECT->value);
    }
}
