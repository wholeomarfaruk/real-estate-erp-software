<?php

namespace App\Livewire\Suppliers\Show;

use App\Models\Supplier;
use Livewire\Component;

/**
 * Supplier ▸ Details tab (module 1 of 4).
 * Full-page Livewire component bound to a route. Shares the hero/KPI/tab chrome
 * via the <x-supplier.shell> Blade component.
 */
class Details extends Component
{
    public Supplier $supplier;

    public function mount(Supplier $supplier): void
    {
        $this->supplier = $supplier;
    }

    public function render()
    {
        // 12-month purchase/payment trend. Replace with a real aggregate query:
        //   ->purchaseInvoices()->selectRaw('month, SUM(total) ...')->groupBy(...)
        $trend = [
            ['m' => 'Jul', 'purchased' => 1.2, 'paid' => 1.2,  'due' => 0],
            ['m' => 'Aug', 'purchased' => 1.8, 'paid' => 1.8,  'due' => 0],
            ['m' => 'Sep', 'purchased' => 1.4, 'paid' => 1.3,  'due' => 0.1],
            ['m' => 'Oct', 'purchased' => 2.1, 'paid' => 2.0,  'due' => 0.1],
            ['m' => 'Nov', 'purchased' => 1.6, 'paid' => 1.6,  'due' => 0],
            ['m' => 'Dec', 'purchased' => 2.4, 'paid' => 2.2,  'due' => 0.2],
            ['m' => 'Jan', 'purchased' => 1.1, 'paid' => 1.1,  'due' => 0],
            ['m' => 'Feb', 'purchased' => 1.9, 'paid' => 1.7,  'due' => 0.2],
            ['m' => 'Mar', 'purchased' => 1.5, 'paid' => 1.5,  'due' => 0],
            ['m' => 'Apr', 'purchased' => 2.2, 'paid' => 2.0,  'due' => 0.2],
            ['m' => 'May', 'purchased' => 1.7, 'paid' => 1.6,  'due' => 0.1],
            ['m' => 'Jun', 'purchased' => 2.4, 'paid' => 1.56, 'due' => 0.84],
        ];

        // documents json on the supplier = array of file IDs only.
        $documents = $this->supplier->documents ?? [40192, 40193, 40194];

        return view('livewire.suppliers.show.details', [
            'trend'     => $trend,
            'documents' => $documents,
        ]);
    }
}
