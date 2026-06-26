<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountEntryCategory extends Model
{
    protected $fillable = [
        'key', 'title', 'description', 'icon', 'color',
        'is_locked', 'sort_order', 'is_active',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function types(): HasMany
    {
        return $this->hasMany(AccountEntryType::class, 'category_key', 'key');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function isLocked(): bool
    {
        return $this->is_locked;
    }
}
