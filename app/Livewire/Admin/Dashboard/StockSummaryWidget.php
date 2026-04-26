<?php

namespace App\Livewire\Admin\Dashboard;

use App\Models\StockBalance;
use Livewire\Component;

class StockSummaryWidget extends Component
{
    public $totalItems = 0;
    public $lowStockItems = 0;

    public function mount()
    {
        $this->calculateStockSummary();
    }

    public function calculateStockSummary()
    {
        try {
            if (class_exists(StockBalance::class)) {
                $this->totalItems = StockBalance::query()->sum('quantity') ?? 0;
                $this->lowStockItems = StockBalance::query()
                    ->whereRaw('quantity < reorder_level')
                    ->count() ?? 0;
            }
        } catch (\Exception $e) {
            \Log::error('Error calculating stock summary: ' . $e->getMessage());
            $this->totalItems = 0;
            $this->lowStockItems = 0;
        }
    }

    public function render()
    {
        return view('livewire.admin.dashboard.stock-summary');
    }
}
