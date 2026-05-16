<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertySale extends Model
{
    use SoftDeletes;

    protected $table = 'property_sales';

    protected $fillable = [
        'sale_number',
        'property_id',
        'sale_type',
        'property_unit_id',
        'customer_id',
        'package_id',
        'sale_date',
        'contract_date',
        'sale_amount',
        'discount_amount',
        'tax_amount',
        'net_amount',
        'down_payment_amount',
        'payment_terms',
        'payment_status',
        'installment_month_no',
        'installment_amount',
        'installment_status',
        'status',
        'sales_representative',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'sale_date'            => 'date',
        'contract_date'        => 'date',
        'sale_amount'          => 'decimal:2',
        'discount_amount'      => 'decimal:2',
        'tax_amount'           => 'decimal:2',
        'net_amount'           => 'decimal:2',
        'down_payment_amount'  => 'decimal:2',
        'installment_amount'   => 'decimal:2',
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

    // ── Relationships ────────────────────────────────────────────────────────

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function propertyUnit(): BelongsTo
    {
        return $this->belongsTo(PropertyUnit::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function paymentSchedules(): HasMany
    {
        return $this->hasMany(PaymentSchedule::class)->orderBy('payment_category')->orderBy('sequence_no');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function totalScheduled(): float
    {
        return (float) $this->paymentSchedules()->sum('amount');
    }

    public function totalPaid(): float
    {
        return (float) $this->paymentSchedules()->sum('paid_amount');
    }

    public function totalDue(): float
    {
        return (float) $this->paymentSchedules()->sum('due_amount');
    }
}
