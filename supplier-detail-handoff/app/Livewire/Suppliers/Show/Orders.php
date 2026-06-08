<?php

namespace App\Livewire\Suppliers\Show;

use App\Models\Supplier;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Supplier ▸ Purchase Orders tab (module 3 of 4).
 */
class Orders extends Component
{
    use WithPagination;

    public Supplier $supplier;

    public function mount(Supplier $supplier): void
    {
        $this->supplier = $supplier;
    }

    /** Demo POs. Swap for $this->supplier->purchaseOrders()->latest()->paginate(15). */
    #[Computed]
    public function orders()
    {
        return collect([
            ['no'=>'PO-2026-0063','date'=>'2026-05-29','items'=>'320 bags OPC · 18m³ RMC','val'=>1160000,'delivery'=>'2026-06-10','status'=>'ordered'],
            ['no'=>'PO-2026-0062','date'=>'2026-05-21','items'=>'500 bags OPC','val'=>680000,'delivery'=>'2026-05-28','status'=>'received'],
            ['no'=>'PO-2026-0061','date'=>'2026-05-18','items'=>'24m³ RMC · admixture','val'=>842000,'delivery'=>'2026-05-30','status'=>'received'],
            ['no'=>'PO-2026-0060','date'=>'2026-05-09','items'=>'600 bags OPC','val'=>810000,'delivery'=>'2026-05-16','status'=>'received'],
            ['no'=>'PO-2026-0059','date'=>'2026-05-02','items'=>'40m³ RMC','val'=>1180000,'delivery'=>'2026-05-12','status'=>'received'],
            ['no'=>'PO-2026-0058','date'=>'2026-04-24','items'=>'250 bags OPC · sand','val'=>420000,'delivery'=>'2026-04-30','status'=>'received'],
            ['no'=>'PO-2026-0057','date'=>'2026-04-19','items'=>'22m³ RMC','val'=>640000,'delivery'=>'—','status'=>'cancelled'],
        ]);
    }

    public function view(string $no)
    {
        // TODO: return $this->redirectRoute('purchase-orders.show', $no);
        $this->dispatch('toast', message: "Opening {$no}…");
    }

    public function downloadPdf(string $no)
    {
        // TODO: stream PO PDF. Button stays regardless of backend readiness.
        $this->dispatch('toast', message: "Preparing {$no}.pdf…");
    }

    public function render()
    {
        return view('livewire.suppliers.show.orders');
    }
}
