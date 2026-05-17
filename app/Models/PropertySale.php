<?php

namespace App\Models;

use App\Enums\Property\PropertySaleType;
use App\Traits\HasPaymentSchedules;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertySale extends Model
{
    use SoftDeletes, HasPaymentSchedules;

    protected $table = 'property_sales';

    protected $fillable = [
        'sale_number',
        'sale_type',
        'property_id',
        'property_unit_id',
        'customer_id',
        'sale_date',
        'contract_date',
        // financials
        'sale_amount',
        'discount_amount',
        'tax_amount',
        'net_amount',
        'down_payment_amount',
        'payment_terms',
        'payment_status',
        // schedule
        'is_scheduled',
        'schedule_count',
        'schedule_amount',
        'schedule_name',
        'schedule_type',
        'schedule_day',
        'schedule_start_date',
        'schedule_status',
        // rent
        'rent_start_date',
        'rent_end_date',
        'security_deposit_amount',
        'is_renewal',
        'renewal_date',
        // meta
        'status',
        'sales_representative',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'sale_date'               => 'date',
        'contract_date'           => 'date',
        'sale_amount'             => 'decimal:2',
        'discount_amount'         => 'decimal:2',
        'tax_amount'              => 'decimal:2',
        'net_amount'              => 'decimal:2',
        'down_payment_amount'     => 'decimal:2',
        'schedule_amount'         => 'decimal:2',
        'security_deposit_amount' => 'decimal:2',
        'schedule_start_date'     => 'date',
        'rent_start_date'         => 'date',
        'rent_end_date'           => 'date',
        'renewal_date'            => 'date',
        'is_scheduled'            => 'boolean',
        'is_renewal'              => 'boolean',
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
        return $this->hasMany(PaymentSchedule::class)
            ->orderByRaw("FIELD(payment_category,'down_payment','security_deposit','installment','monthly_rent','extra_charge','manual_charge')")
            ->orderBy('sequence_no')
            ->orderBy('due_date');
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

    public function isSale(): bool
    {
        return $this->sale_type === 'sale';
    }

    public function isRent(): bool
    {
        return $this->sale_type === 'rent';
    }

    public function saleTypeLabel(): string
    {
        return match($this->sale_type) {
            'sale' => 'Property Sale',
            'rent' => 'Rent',
            default => ucfirst($this->sale_type ?? 'N/A'),
        };
    }
}
