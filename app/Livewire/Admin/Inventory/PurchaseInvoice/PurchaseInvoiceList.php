<?php

namespace App\Livewire\Admin\Inventory\PurchaseInvoice;

use App\Enums\Inventory\PurchaseInvoiceStatus;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Services\Inventory\PurchaseInvoiceService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseInvoiceList extends Component
{
    use InteractsWithInventoryAccess;
    use WithPagination;

    public string $search        = '';
    public string $statusFilter  = '';
    public ?int   $supplierFilter = null;
    public ?string $dateFrom     = null;
    public ?string $dateTo       = null;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('inventory.purchase_invoice.view');
    }

    public function updatedSearch(): void        { $this->resetPage(); }
    public function updatedStatusFilter(): void  { $this->resetPage(); }
    public function updatedSupplierFilter(): void{ $this->resetPage(); }
    public function updatedDateFrom(): void      { $this->resetPage(); }
    public function updatedDateTo(): void        { $this->resetPage(); }

    public function cancelInvoice(int $invoiceId): void
    {
        $this->authorizePermission('inventory.purchase_invoice.cancel');

        $invoice = PurchaseInvoice::query()->find($invoiceId);
        if (! $invoice) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Invoice not found.']);
            return;
        }

        try {
            app(PurchaseInvoiceService::class)->cancelInvoice($invoice, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Invoice cancelled successfully.']);
        } catch (\Throwable $e) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function deleteInvoice(int $invoiceId): void
    {
        $this->authorizePermission('inventory.purchase_invoice.delete');

        $invoice = PurchaseInvoice::query()->find($invoiceId);
        if (! $invoice) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Invoice not found.']);
            return;
        }

        if (! $invoice->status->canBeDeleted()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only pending invoices with no transactions can be deleted.']);
            return;
        }

        if ($invoice->transaction_id) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Cannot delete: accounting entries already exist.']);
            return;
        }

        $invoice->items()->delete();
        $invoice->delete();

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Invoice deleted successfully.']);
    }

    public function render(): View
    {
        $this->authorizePermission('inventory.purchase_invoice.view');

        $invoices = PurchaseInvoice::query()
            ->with([
                'supplier:id,name',
                'stockReceive:id,receive_no,receive_date',
                'approver:id,name',
            ])
            ->withCount('items')
            ->when($this->search !== '', function (Builder $q): void {
                $q->where(function (Builder $sub): void {
                    $sub->where('invoice_no', 'like', '%' . $this->search . '%')
                        ->orWhere('supplier_invoice_no', 'like', '%' . $this->search . '%')
                        ->orWhereHas('supplier', fn (Builder $s): Builder => $s->where('name', 'like', '%' . $this->search . '%'));
                });
            })
            ->when($this->statusFilter !== '', fn (Builder $q): Builder => $q->where('status', $this->statusFilter))
            ->when($this->supplierFilter, fn (Builder $q): Builder => $q->where('supplier_id', $this->supplierFilter))
            ->when($this->dateFrom, fn (Builder $q): Builder => $q->whereDate('invoice_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $q): Builder => $q->whereDate('invoice_date', '<=', $this->dateTo))
            ->latest('invoice_date')
            ->latest('id')
            ->paginate(20);

        // Stats
        $statsBase = PurchaseInvoice::query();
        $totalCount    = (clone $statsBase)->count();
        $pendingCount  = (clone $statsBase)->where('status', PurchaseInvoiceStatus::PENDING->value)->count();
        $approvedCount = (clone $statsBase)->whereIn('status', [
            PurchaseInvoiceStatus::APPROVED->value,
            PurchaseInvoiceStatus::PARTIALLY_PAID->value,
        ])->count();
        $paidCount     = (clone $statsBase)->where('status', PurchaseInvoiceStatus::PAID->value)->count();

        return view('livewire.admin.inventory.purchase-invoice.purchase-invoice-list', [
            'invoices'      => $invoices,
            'statuses'      => PurchaseInvoiceStatus::cases(),
            'suppliers'     => Supplier::query()->active()->orderBy('name')->get(['id', 'name']),
            'totalCount'    => $totalCount,
            'pendingCount'  => $pendingCount,
            'approvedCount' => $approvedCount,
            'paidCount'     => $paidCount,
        ])->layout('layouts.admin.admin');
    }
}
