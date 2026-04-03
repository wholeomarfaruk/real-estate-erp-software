<?php

namespace App\Models;

use App\Enums\Inventory\StoreType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    protected $fillable = [
        'name',
        'code',
        'type',
        'project_id',
        'manager_user_id',
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

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'store_user')
            ->withPivot(['is_primary'])
            ->withTimestamps();
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
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

    public function scopeManagedBy(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $subQuery) use ($userId): void {
            $subQuery->where('manager_user_id', $userId)
                ->orWhereHas('users', function (Builder $storeUserQuery) use ($userId): void {
                    $storeUserQuery->where('users.id', $userId);
                });
        });
    }
}
