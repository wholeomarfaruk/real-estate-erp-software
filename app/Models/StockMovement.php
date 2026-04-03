<?php

namespace App\Models;

use App\Enums\Inventory\StockMovementDirection;
use App\Enums\Inventory\StockMovementType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'movement_date',
        'product_id',
        'store_id',
        'project_id',
        'supplier_id',
        'direction',
        'movement_type',
        'quantity',
        'unit_price',
        'total_price',
        'balance_after',
        'reference_type',
        'reference_id',
        'reference_no',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'movement_date' => 'datetime',
        'direction' => StockMovementDirection::class,
        'movement_type' => StockMovementType::class,
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'balance_after' => 'decimal:3',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeDateBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn (Builder $builder): Builder => $builder->whereDate('movement_date', '>=', $from))
            ->when($to, fn (Builder $builder): Builder => $builder->whereDate('movement_date', '<=', $to));
    }

    public function scopeForProduct(Builder $query, ?int $productId): Builder
    {
        return $query->when($productId, fn (Builder $builder): Builder => $builder->where('product_id', $productId));
    }

    public function scopeForStore(Builder $query, ?int $storeId): Builder
    {
        return $query->when($storeId, fn (Builder $builder): Builder => $builder->where('store_id', $storeId));
    }

    public function scopeForProject(Builder $query, ?int $projectId): Builder
    {
        return $query->when($projectId, fn (Builder $builder): Builder => $builder->where('project_id', $projectId));
    }

    public function scopeForSupplier(Builder $query, ?int $supplierId): Builder
    {
        return $query->when($supplierId, fn (Builder $builder): Builder => $builder->where('supplier_id', $supplierId));
    }

    public function scopeForMovementType(Builder $query, ?string $movementType): Builder
    {
        return $query->when($movementType, fn (Builder $builder): Builder => $builder->where('movement_type', $movementType));
    }
}
