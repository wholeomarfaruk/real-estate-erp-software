<?php

namespace App\Livewire\Admin\Supplier\Supplier;

use App\Livewire\Admin\Supplier\Concerns\InteractsWithSupplierAccess;
use App\Livewire\Admin\Supplier\Concerns\InteractsWithSupplierForm;
use App\Livewire\Forms\SupplierForm;
use App\Livewire\Traits\WithMediaPicker;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierList extends Component
{
    use InteractsWithSupplierAccess;
    use InteractsWithSupplierForm;
    use WithPagination;
    use WithMediaPicker;

    public SupplierForm $form;

    // Documents proxy (WithMediaPicker trait writes $this->$field directly)
    public array $documents = [];

    // ── Filters ──────────────────────────────────────────────────────────────
    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $statusFilter = 'all';

    #[Url(history: true)]
    public string $balanceFilter = 'all';

    #[Url(history: true)]
    public string $sortBy = 'recent';

    public function mount(): void
    {
        $this->authorizePermission('supplier.list.view');
        $this->refreshNextCode();
    }

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
        $counts = Supplier::query()
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN is_blocked = 0 AND status = 1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN is_blocked = 0 AND status = 0 THEN 1 ELSE 0 END) as inactive,
                SUM(CASE WHEN is_blocked = 1 THEN 1 ELSE 0 END) as blocked
            ")
            ->first();

        $payable  = 0.0;
        $advance  = 0.0;
        $invoices = 0;

        // Payable (total due) comes from the invoices themselves.
        if (DB::getSchemaBuilder()->hasTable('purchase_invoices')) {
            $payable  = (float) (DB::table('purchase_invoices')->sum('due_amount') ?? 0);
            $invoices = (int) (DB::table('purchase_invoices')->count() ?? 0);
        }

        // Available advance = advance transaction debits − everything adjusted.
        $advanceDebit = (float) DB::table('transactions')
            ->join('transaction_lines', 'transaction_lines.transaction_id', '=', 'transactions.id')
            ->where('transactions.type', \App\Enums\Accounts\TransactionType::ADVANCE->value)
            ->where('transactions.reference_type', Supplier::class)
            ->sum('transaction_lines.debit');

        $advanceAdjusted = (float) DB::table('advance_adjustments')
            ->join('transactions', 'transactions.id', '=', 'advance_adjustments.advance_transaction_id')
            ->where('transactions.type', \App\Enums\Accounts\TransactionType::ADVANCE->value)
            ->where('transactions.reference_type', Supplier::class)
            ->sum('advance_adjustments.amount');

        $advance = round(max(0, $advanceDebit - $advanceAdjusted), 2);

        return [
            'total'    => (int) $counts->total,
            'active'   => (int) $counts->active,
            'inactive' => (int) $counts->inactive,
            'blocked'  => (int) $counts->blocked,
            'payable'  => $payable,
            'advance'  => $advance,
            'invoices' => $invoices,
        ];
    }

    /* ───────────────────────────── The list ───────────────────────────────── */
    public function render(): View
    {
        $this->authorizePermission('supplier.list.view');

        $hasPurchaseInvoices = DB::getSchemaBuilder()->hasTable('purchase_invoices');

        $query = Supplier::query()
            ->search($this->search)
            ->statusKey($this->statusFilter === 'all' ? '' : $this->statusFilter);

        if ($hasPurchaseInvoices) {
            // Per-supplier balance = available advance − outstanding due.
            //   balance < 0  → net payable (due)
            //   balance > 0  → net advance held
            // Built as a single selectRaw (before withCount) so the select-clause
            // bindings stay correctly ordered ahead of the count bindings.
            $advanceType = \App\Enums\Accounts\TransactionType::ADVANCE->value;

            $dueSub = DB::table('purchase_invoices')
                ->selectRaw('COALESCE(SUM(due_amount), 0)')
                ->whereColumn('purchase_invoices.supplier_id', 'suppliers.id');

            $advDebitSub = DB::table('transactions')
                ->join('transaction_lines', 'transaction_lines.transaction_id', '=', 'transactions.id')
                ->selectRaw('COALESCE(SUM(transaction_lines.debit), 0)')
                ->where('transactions.type', $advanceType)
                ->where('transactions.reference_type', Supplier::class)
                ->whereColumn('transactions.reference_id', 'suppliers.id');

            $advAdjustedSub = DB::table('advance_adjustments')
                ->join('transactions', 'transactions.id', '=', 'advance_adjustments.advance_transaction_id')
                ->selectRaw('COALESCE(SUM(advance_adjustments.amount), 0)')
                ->where('transactions.type', $advanceType)
                ->where('transactions.reference_type', Supplier::class)
                ->whereColumn('transactions.reference_id', 'suppliers.id');

            $query->select('suppliers.*')->selectRaw(
                "(({$advDebitSub->toSql()}) - ({$advAdjustedSub->toSql()})) - ({$dueSub->toSql()}) as balance",
                array_merge($advDebitSub->getBindings(), $advAdjustedSub->getBindings(), $dueSub->getBindings())
            );

            $query->withCount([
                'purchaseInvoices as purchase_invoices_count',
                'purchaseInvoices as unpaid_invoices_count' => fn ($q) => $q->where('status', '!=', 'paid'),
            ]);

            $query
                ->when($this->balanceFilter === 'due',     fn ($q) => $q->having('balance', '<', 0))
                ->when($this->balanceFilter === 'advance', fn ($q) => $q->having('balance', '>', 0))
                ->when($this->balanceFilter === 'settled', fn ($q) => $q->having('balance', '=', 0))
                ->when($this->sortBy === 'due',      fn ($q) => $q->orderBy('balance', 'asc'))
                ->when($this->sortBy === 'invoices', fn ($q) => $q->orderByDesc('purchase_invoices_count'));
        } else {
            $query->addSelect(DB::raw('0 as purchase_invoices_count, 0 as unpaid_invoices_count, 0 as balance'));
        }

        $query
            ->when($this->sortBy === 'recent', fn ($q) => $q->latest())
            ->when($this->sortBy === 'name',   fn ($q) => $q->orderBy('name'));

        $suppliers = $query->paginate(15);

        return view('livewire.admin.supplier.supplier.supplier-list', [
            'suppliers' => $suppliers,
        ])->layout('layouts.admin.admin');
    }

    /* ─────────────────────── Row actions (status) ─────────────────────────── */
    public function toggleActive(int $id): void
    {
        $this->authorizePermission('supplier.status.change');
        $s = Supplier::findOrFail($id);
        $s->update(['status' => ! $s->status, 'updated_by' => auth()->id()]);
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Status updated.']);
    }

    public function block(int $id): void
    {
        $this->authorizePermission('supplier.status.change');
        Supplier::whereKey($id)->update(['is_blocked' => true, 'updated_by' => auth()->id()]);
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Supplier blocked.']);
    }

    public function unblock(int $id): void
    {
        $this->authorizePermission('supplier.status.change');
        Supplier::whereKey($id)->update(['is_blocked' => false, 'updated_by' => auth()->id()]);
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Supplier unblocked.']);
    }

    /* ─────────────────────── Stubs ─────────────────────────────────────────── */
    public function export(): void
    {
        $this->dispatch('toast', ['type' => 'info', 'message' => 'Export coming soon.']);
    }

    public function downloadPo(int $id): void
    {
        $this->dispatch('toast', ['type' => 'info', 'message' => 'Download PO coming soon.']);
    }

    public function view(int $id): void
    {
        $this->redirect(route('admin.supplier.suppliers.show.details', $id));
    }
}
