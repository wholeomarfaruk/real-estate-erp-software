<?php

namespace App\Livewire\Suppliers\Show;

use App\Livewire\Admin\Supplier\Concerns\InteractsWithSupplierAccess;
use App\Livewire\Admin\Supplier\Concerns\InteractsWithSupplierForm;
use App\Livewire\Forms\SupplierForm;
use App\Livewire\Traits\WithMediaPicker;
use App\Models\Supplier;
use Livewire\Attributes\On;
use Livewire\Component;

class Details extends Component
{
    use InteractsWithSupplierAccess;
    use InteractsWithSupplierForm;
    use WithMediaPicker;

    public Supplier $supplier;

    public SupplierForm $form;

    // Documents proxy (WithMediaPicker writes $this->$field directly).
    public array $documents = [];

    public function mount(Supplier $supplier): void
    {
        $this->supplier = $supplier;
        $this->refreshNextCode();
    }

    /** After a save, refresh the displayed supplier so edits show immediately. */
    #[On('supplier-saved')]
    public function refreshSupplier(): void
    {
        $this->supplier->refresh();
    }

    public function render()
    {
        $supplier = $this->supplier->loadCount([
            'purchaseInvoices',
            'purchaseOrders',
            'purchaseFunds',
            'stockReceives',
            'purchaseReturns',
        ]);

        $totalPurchased = (float) $supplier->purchaseInvoices()->sum('total_amount');
        $totalPaid      = (float) $supplier->purchaseInvoices()->sum('paid_amount');
        $totalDue       = (float) $supplier->purchaseInvoices()->sum('due_amount');

        // Advance still held by the supplier (net of what has been applied to invoices).
        $advanceHeld = round($supplier->advanceRemaining(), 2);

        // True net position with the supplier:
        //   > 0 → supplier holds our advance (asset)
        //   < 0 → we owe the supplier (payable)
        $netBalance = round($advanceHeld - $totalDue, 2);

        // Ledger entry count via transactions linked to invoices
        $ledgerCount = $supplier->purchaseInvoices()
            ->whereNotNull('transaction_id')
            ->count();

        // 12-month purchase & payment trend grouped by month
        $trend = $supplier->purchaseInvoices()
            ->selectRaw("DATE_FORMAT(invoice_date, '%b') as m, SUM(total_amount)/1000000 as purchased, SUM(paid_amount)/1000000 as paid, SUM(due_amount)/1000000 as due")
            ->where('invoice_date', '>=', now()->subMonths(11)->startOfMonth())
            ->groupByRaw("DATE_FORMAT(invoice_date, '%Y-%m'), DATE_FORMAT(invoice_date, '%b')")
            ->orderByRaw("DATE_FORMAT(invoice_date, '%Y-%m')")
            ->get()
            ->map(fn ($r) => [
                'm'         => $r->m,
                'purchased' => round((float) $r->purchased, 2),
                'paid'      => round((float) $r->paid, 2),
                'due'       => round((float) $r->due, 2),
            ])
            ->toArray();

        $documents = $supplier->documents ?? [];

        return view('livewire.suppliers.show.details', [
            'supplier'       => $supplier,
            'totalPurchased' => $totalPurchased,
            'totalPaid'      => $totalPaid,
            'totalDue'       => $totalDue,
            'advanceHeld'    => $advanceHeld,
            'netBalance'     => $netBalance,
            'ledgerCount'    => $ledgerCount,
            'trend'          => $trend,
            'documents'      => $documents,
        ])->layout('layouts.admin.admin');
    }
}
