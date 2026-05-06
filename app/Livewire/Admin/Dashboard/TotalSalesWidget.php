<?php

namespace App\Livewire\Admin\Dashboard;

use App\Models\Transaction;
use Livewire\Component;

class TotalSalesWidget extends Component
{
    public $totalSales = 0;

    public function mount()
    {
        $this->calculateTotalSales();
    }

    public function calculateTotalSales()
    {
        try {
            // Check if Transaction model exists
            if (class_exists(Transaction::class)) {
                $this->totalSales = Transaction::query()
                    ->where('transaction_type', 'sales')
                    ->sum('amount') ?? 0;
            }
        } catch (\Exception $e) {
            \Log::error('Error calculating total sales: ' . $e->getMessage());
            $this->totalSales = 0;
        }
    }

    public function render()
    {
        return view('livewire.admin.dashboard.total-sales');
    }
}
