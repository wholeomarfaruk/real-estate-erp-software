<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyPaymentItem extends Model
{
    protected $table = 'property_payment_items';

    protected $fillable = [
        'property_payment_id',
        'payment_schedule_id',
        'amount',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function propertyPayment(): BelongsTo
    {
        return $this->belongsTo(PropertyPayment::class);
    }

    public function paymentSchedule(): BelongsTo
    {
        return $this->belongsTo(PaymentSchedule::class);
    }
}
