<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertySale extends Model
{
    use SoftDeletes;

    protected $table = 'property_sales';

    protected $fillable = [
        'sale_number',
        'property_unit_id',
        'customer_id',
        'sale_date',
        'contract_date',
        'sale_amount',
        'discount_amount',
        'tax_amount',
        'net_amount',
        'payment_terms',
        'payment_status',
        'status',
        'sales_representative',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'sale_date'       => 'date',
        'contract_date'   => 'date',
        'sale_amount'     => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount'      => 'decimal:2',
        'net_amount'      => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->sale_number)) {
                $nextId = (static::withTrashed()->max('id') ?? 0) + 1;
                $model->sale_number = 'SALE-' . str_pad($nextId, 7, '0', STR_PAD_LEFT);
            }
        });
    }

    public function propertyUnit(): BelongsTo
    {
        return $this->belongsTo(PropertyUnit::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
