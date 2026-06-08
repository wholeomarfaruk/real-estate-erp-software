<?php

namespace App\Livewire\Suppliers;

use App\Livewire\Forms\SupplierForm;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierList extends Component
{
    use WithPagination;

    public SupplierForm $form;

    /* ── Filters (kept in the URL so the list is shareable/back-button safe) ── */
    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $statusFilter = 'all';      // all | active | inactive | blocked

    #[Url(history: true)]
    public string $balanceFilter = 'all';     // all | due | advance | settled

    #[Url(history: true)]
    public string $sortBy = 'recent';         // recent | due | invoices | name

    public int $nextCode = 0;                 // for the modal subtitle (SUP-000xxx)

    public function mount(): void
    {
        $this->nextCode = (int) (Supplier::withTrashed()->max('id') ?? 0) + 1;
    }

    /* Reset to page 1 whenever a filter changes (Livewire calls these hooks). */
    public function updatedSearch(): void        { $this->resetPage(); }
    public function updatedStatusFilter(): void  { $this->resetPage(); }
    public function updatedBalanceFilter(): void { $this->resetPage(); }
    public function updatedSortBy(): void        { $this->resetPage(); }

    public function setStatus(string $key): void
    {
        $this->statusFilter = $key;
        $this->resetPage();
    }

    /* ───────────────────────────── KPI strip ──────────────────────────────── */
    #[Computed]
    public function stats(): array
    {
        // One grouped query for the status counts.
        $counts = Supplier::query()
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN is_blocked = 0 AND status = 1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN is_blocked = 0 AND status = 0 THEN 1 ELSE 0 END) as inactive,
                SUM(CASE WHEN is_blocked = 1 THEN 1 ELSE 0 END) as blocked
            ")
            ->first();

        return [
            'total'    => (int) $counts->total,
            'active'   => (int) $counts->active,
            'inactive' => (int) $counts->inactive,
            'blocked'  => (int) $counts->blocked,
            // Wire these to your ledger aggregates:
            'payable'  => (float) (DB::table('purchase_payables')->sum('due_amount') ?? 0),
            'advance'  => (float) (DB::table('purchase_payables')->sum('advance_amount') ?? 0),
            'invoices' => (int)   (DB::table('purchase_invoices')->count() ?? 0),
        ];
    }

    /* ───────────────────────────── The list ───────────────────────────────── */
    public function render()
    {
        $suppliers = Supplier::query()
            ->search($this->search)
            ->statusKey($this->statusFilter)
            ->withCount([
                'purchaseInvoices as purchase_invoices_count',
                'purchaseInvoices as unpaid_invoices_count' => fn ($q) => $q->where('status', '!=', 'paid'),
            ])
            // Net balance as a column so we can filter + sort in SQL (no N+1).
            // advance - due. Adjust the sub-selects to your real ledger.
            ->addSelect([
                'balance' => DB::table('purchase_payables')
                    ->selectRaw('COALESCE(SUM(advance_amount) - SUM(due_amount), 0)')
                    ->whereColumn('purchase_payables.supplier_id', 'suppliers.id'),
            ])
            ->when($this->balanceFilter === 'due',     fn ($q) => $q->having('balance', '<', 0))
            ->when($this->balanceFilter === 'advance', fn ($q) => $q->having('balance', '>', 0))
            ->when($this->balanceFilter === 'settled', fn ($q) => $q->having('balance', '=', 0))
            ->when($this->sortBy === 'recent',   fn ($q) => $q->latest())
            ->when($this->sortBy === 'due',      fn ($q) => $q->orderBy('balance', 'asc'))  // most-negative first
            ->when($this->sortBy === 'invoices', fn ($q) => $q->orderByDesc('purchase_invoices_count'))
            ->when($this->sortBy === 'name',     fn ($q) => $q->orderBy('name'))
            ->paginate(15);

        return view('livewire.suppliers.supplier-list', [
            'suppliers' => $suppliers,
        ]);
    }

    /* ───────────────────────────── Create ─────────────────────────────────── */
    public function save(): void
    {
        $this->form->validate();

        $supplier = Supplier::create($this->form->toAttributes() + [
            'created_by' => auth()->id(),
        ]);

        $this->form->reset();
        $this->mount();                         // refresh next code
        $this->dispatch('supplier-saved', name: $supplier->name);  // Alpine closes the modal
        $this->dispatch('toast', message: "Supplier “{$supplier->name}” created.");
    }

    /* ─────────────────────── Row actions (status) ─────────────────────────── */
    public function toggleActive(int $id): void
    {
        $s = Supplier::findOrFail($id);
        $s->update(['status' => ! $s->status, 'updated_by' => auth()->id()]);
        $this->dispatch('toast', message: 'Status updated.');
    }

    public function block(int $id): void
    {
        Supplier::whereKey($id)->update(['is_blocked' => true, 'updated_by' => auth()->id()]);
        $this->dispatch('toast', message: 'Supplier blocked.');
    }

    public function unblock(int $id): void
    {
        Supplier::whereKey($id)->update(['is_blocked' => false, 'updated_by' => auth()->id()]);
        $this->dispatch('toast', message: 'Supplier unblocked.');
    }

    /* ─────────────────────── Stubs — UI buttons exist regardless ───────────── */
    /* The buttons stay in the markup even if these aren't wired to real exports
       yet. Fill them in later; nothing breaks in the meantime.                 */

    public function export()
    {
        // TODO: stream a CSV / xlsx of the current filtered query.
        $this->dispatch('toast', message: 'Export coming soon.');
    }

    public function downloadPo(int $id)
    {
        // TODO: generate the supplier's purchase-order PDF.
        $this->dispatch('toast', message: 'Download PO coming soon.');
    }

    public function view(int $id)
    {
        // TODO: route to the Supplier detail page once it exists.
        // return $this->redirectRoute('suppliers.show', $id);
    }

    public function edit(int $id)
    {
        // TODO: load $id into $this->form and reuse the modal in edit mode.
    }
}
