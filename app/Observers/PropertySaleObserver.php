<?php

namespace App\Observers;

use App\Models\PropertySale;

class PropertySaleObserver
{
    /**
     * Schedule regeneration is handled explicitly in Livewire components
     * to avoid double-generation. The observer handles post-update sync.
     */
    public function updating(PropertySale $sale): void
    {
        // Mark schedule_status based on payment progress
        if ($sale->isDirty('payment_status')) {
            if ($sale->payment_status === 'paid') {
                $sale->schedule_status = 'complete';
            } elseif ($sale->payment_status === 'pending') {
                $sale->schedule_status = 'active';
            }
        }
    }
}
