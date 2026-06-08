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
            ->with(['purchaseOrder:id,po_no', 'releaser:id,name'])
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

        return [
            'total'        => $rows->total ?? 0,
            'total_amount' => $rows->total_amount ?? 0,
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
