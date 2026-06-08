<?php

namespace App\Livewire\Suppliers\Show;

use App\Models\Supplier;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Supplier ▸ Advance Payments tab (module 4 of 4).
 */
class Advances extends Component
{
    use WithPagination;

    public Supplier $supplier;

    public function mount(Supplier $supplier): void
    {
        $this->supplier = $supplier;
    }

    /** Demo advances. Swap for $this->supplier->purchaseFunds()->latest()->paginate(15). */
    #[Computed]
    public function advances()
    {
        return collect([
            ['ref'=>'ADV-2026-0031','date'=>'2026-05-18','po'=>'PO-2026-0061','amt'=>300000,'adj'=>300000,'bal'=>0,'status'=>'adjusted'],
            ['ref'=>'ADV-2026-0027','date'=>'2026-05-02','po'=>'PO-2026-0059','amt'=>500000,'adj'=>500000,'bal'=>0,'status'=>'adjusted'],
            ['ref'=>'ADV-2026-0022','date'=>'2026-04-19','po'=>'PO-2026-0057','amt'=>250000,'adj'=>250000,'bal'=>0,'status'=>'adjusted'],
            ['ref'=>'ADV-2026-0018','date'=>'2026-03-28','po'=>'PO-2026-0048','amt'=>700000,'adj'=>700000,'bal'=>0,'status'=>'adjusted'],
            ['ref'=>'ADV-2026-0013','date'=>'2026-03-02','po'=>'PO-2026-0045','amt'=>300000,'adj'=>300000,'bal'=>0,'status'=>'adjusted'],
            ['ref'=>'ADV-2026-0009','date'=>'2026-02-14','po'=>'PO-2026-0042','amt'=>900000,'adj'=>900000,'bal'=>0,'status'=>'adjusted'],
            ['ref'=>'ADV-2026-0004','date'=>'2026-01-22','po'=>'PO-2026-0038','amt'=>900000,'adj'=>900000,'bal'=>0,'status'=>'adjusted'],
        ]);
    }

    public function downloadPdf(string $ref)
    {
        $this->dispatch('toast', message: "Preparing {$ref}.pdf…");
    }

    public function render()
    {
        return view('livewire.suppliers.show.advances');
    }
}
