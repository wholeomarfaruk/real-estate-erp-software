<?php

namespace App\Models;

use App\Enums\Projects\CostType;
use App\Enums\Projects\WorkPhase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstimateItem extends Model
{
    protected $fillable = [
        'project_estimate_id',
        'material_id',
        'transaction_category_id',
        'type',
        'name',
        'quantity',
        'unit',
        'rate',
        'total_cost',
        'estimated_qty',
        'estimated_rate',
        'estimated_amount',
        'cost_type',
        'work_phase',
        'sort_order',
        'is_optional',
        'remarks',
    ];

    protected $casts = [
        'quantity'         => 'decimal:2',
        'rate'             => 'decimal:2',
        'total_cost'       => 'decimal:2',
        'estimated_qty'    => 'decimal:2',
        'estimated_rate'   => 'decimal:2',
        'estimated_amount' => 'decimal:2',
        'is_optional'      => 'boolean',
        'cost_type'        => CostType::class,
        'work_phase'       => WorkPhase::class,
    ];

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(ProjectEstimate::class, 'project_estimate_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'material_id');
    }

    public function transactionCategory(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->total_cost       = ($item->quantity ?? 0) * ($item->rate ?? 0);
            $item->estimated_amount = ($item->estimated_qty ?? 0) * ($item->estimated_rate ?? 0);
        });
    }
}
