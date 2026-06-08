<?php

namespace App\Livewire\Suppliers\Show;

use App\Models\Supplier;
use Livewire\Component;

class Details extends Component
{
    public Supplier $supplier;

    public function mount(Supplier $supplier): void
    {
        $this->supplier = $supplier;
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
            'ledgerCount'    => $ledgerCount,
            'trend'          => $trend,
            'documents'      => $documents,
        ])->layout('layouts.admin.admin');
    }
}
