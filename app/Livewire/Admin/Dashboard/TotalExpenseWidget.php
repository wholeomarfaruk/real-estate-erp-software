<?php

namespace App\Livewire\Admin\Dashboard;

use App\Models\Expense;
use Livewire\Component;

class TotalExpenseWidget extends Component
{
    public $totalExpense = 0;

    public function mount()
    {
        $this->calculateTotalExpense();
    }

    public function calculateTotalExpense()
    {
        try {
            if (class_exists(Expense::class)) {
                $this->totalExpense = Expense::query()->sum('amount') ?? 0;
            }
        } catch (\Exception $e) {
            \Log::error('Error calculating total expense: ' . $e->getMessage());
            $this->totalExpense = 0;
        }
    }

    public function render()
    {
        return view('livewire.admin.dashboard.total-expense');
    }
}
