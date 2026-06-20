<?php

namespace App\Livewire\Suppliers\Show;

use App\Models\Supplier;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Advances extends Component
{
    use WithPagination;

    public Supplier $supplier;

    public function mount(Supplier $supplier): void
    {
        $this->supplier = $supplier;
    }

    #[Computed]
    public function advances()
    {
        return $this->supplier->purchaseFunds()
            ->with([
                'purchaseOrder:id,po_no',
                'releaser:id,name',
                // Needed so each row can compute remaining vs adjusted advance.
                'transaction:id,type',
                'transaction.lines:id,transaction_id,debit,credit',
                'transaction.advanceAdjustmentsGiven:id,advance_transaction_id,amount',
            ])
            ->latest('release_date')
            ->latest('id')
            ->paginate(15);
    }

    #[Computed]
    public function stats(): array
    {
        $rows = $this->supplier->purchaseFunds()
            ->selectRaw('COUNT(*) as total, SUM(amount) as total_amount')
            ->first();

        // Open/available advance = completed funds' remaining (net of adjustments).
        $available = $this->supplier->purchaseFunds()
            ->where('status', 'completed')
            ->whereNotNull('transaction_id')
            ->with([
                'transaction:id,type',
                'transaction.lines:id,transaction_id,debit,credit',
                'transaction.advanceAdjustmentsGiven:id,advance_transaction_id,amount',
            ])
            ->get()
            ->sum(fn ($fund) => $fund->remaining);

        return [
            'total'            => $rows->total ?? 0,
            'total_amount'     => $rows->total_amount ?? 0,
            'available_amount' => round((float) $available, 2),
        ];
    }

    public function downloadPdf(int $id): void
    {
        $this->dispatch('toast', type: 'info', message: 'Preparing advance PDF…');
    }

    public function render()
    {
        return view('livewire.suppliers.show.advances')->layout('layouts.admin.admin');
    }
}
