<?php

namespace App\Livewire\Suppliers\Show;

use App\Models\BankingPaymentRequest;
use App\Models\PurchaseOrder;
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
        return $this->supplier->advanceTransactions()
            ->with([
                'lines:id,transaction_id,debit,credit',
                'advanceAdjustmentsGiven:id,advance_transaction_id,amount',
                'creator:id,name',
            ])
            ->latest('datetime')
            ->latest('id')
            ->paginate(15);
    }

    /**
     * PO number lookup (transaction_id => po_no) for the rows on the current page.
     * The PO link lives on the request handler (BankingPaymentRequest.external_data),
     * resolved here for display only.
     */
    #[Computed]
    public function poNumbers(): array
    {
        $txnIds = collect($this->advances->items())->pluck('id')->filter()->values();

        if ($txnIds->isEmpty()) {
            return [];
        }

        $requests = BankingPaymentRequest::query()
            ->whereIn('transaction_id', $txnIds)
            ->get(['transaction_id', 'external_data']);

        $poIds = $requests
            ->map(fn ($r) => (int) ($r->external_data['purchase_order_id'] ?? 0))
            ->filter()->unique()->values();

        $poNoById = $poIds->isEmpty()
            ? collect()
            : PurchaseOrder::whereIn('id', $poIds)->pluck('po_no', 'id');

        return $requests->mapWithKeys(fn ($r) => [
            $r->transaction_id => $poNoById[$r->external_data['purchase_order_id'] ?? null] ?? null,
        ])->all();
    }

    #[Computed]
    public function stats(): array
    {
        $txns = $this->supplier->advanceTransactions()
            ->with([
                'lines:id,transaction_id,debit,credit',
                'advanceAdjustmentsGiven:id,advance_transaction_id,amount',
            ])
            ->get();

        // Total advanced = sum of the advance (debit) movement across transactions.
        $totalAmount = (float) $txns->sum(fn ($t) => (float) $t->lines->sum('debit'));

        // Open/available advance = remaining (net of adjustments) across all advances.
        $available = (float) $txns->sum(fn ($t) => $t->remainingAdvance());

        return [
            'total'            => $txns->count(),
            'total_amount'     => round($totalAmount, 2),
            'available_amount' => round($available, 2),
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
