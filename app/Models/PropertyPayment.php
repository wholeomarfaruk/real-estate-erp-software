<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyPayment extends Model
{
    protected $table = 'property_payments';

    protected $fillable = [
        'payment_no',
        'property_sale_id',
        'payment_date',
        'total_amount',
        'payment_method',
        'reference_no',
        'received_by',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->payment_no)) {
                $nextId = (static::withTrashed()->max('id') ?? 0) + 1;
                $model->payment_no = 'PAY-' . str_pad($nextId, 7, '0', STR_PAD_LEFT);
            }
        });
    }

    public function propertySale(): BelongsTo
    {
        return $this->belongsTo(PropertySale::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PropertyPaymentItem::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
