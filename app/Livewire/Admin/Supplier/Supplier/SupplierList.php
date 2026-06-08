<?php

namespace App\Livewire\Admin\Supplier\Supplier;

use App\Livewire\Admin\Supplier\Concerns\InteractsWithSupplierAccess;
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
    use WithPagination;
    use WithMediaPicker;

    public SupplierForm $form;

    // Modal state — Livewire owns this so edit() can open the modal server-side
    public bool $modalOpen  = false;
    public bool $editMode   = false;
    public ?int $editingId  = null;

    // Documents proxy (WithMediaPicker trait writes $this->$field directly)
    public array $documents = [];

    public function updatedDocuments(): void { $this->form->documents = $this->documents; }

    // ── Filters ──────────────────────────────────────────────────────────────
    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $statusFilter = 'all';

    #[Url(history: true)]
    public string $balanceFilter = 'all';

    #[Url(history: true)]
    public string $sortBy = 'recent';

    public int $nextCode = 0;

    public function mount(): void
    {
        $this->authorizePermission('supplier.list.view');
        $this->nextCode = (int) (Supplier::withTrashed()->max('id') ?? 0) + 1;
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

    // ── Open modal for create ─────────────────────────────────────────────────
    public function openCreate(): void
    {
        $this->authorizePermission('supplier.create');
        $this->resetModal();
        $this->editMode  = false;
        $this->editingId = null;
        $this->modalOpen = true;
    }

    // ── Open modal for edit — loads data instantly, no spinner needed ─────────
    public function edit(int $id): void
    {
        $this->authorizePermission('supplier.edit');

        $s = Supplier::findOrFail($id);

        $this->resetModal();
        $this->editMode  = true;
        $this->editingId = $id;

        // Fill form fields
        $this->form->name             = $s->name;
        $this->form->contact_person   = $s->contact_person ?? '';
        $this->form->email            = $s->email ?? '';
        $this->form->phone            = $s->phone ?? '';
        $this->form->alternate_phone  = $s->alternate_phone ?? '';
        $this->form->address          = $s->address ?? '';
        $this->form->trade_license_no = $s->trade_license_no ?? '';
        $this->form->tin_no           = $s->tin_no ?? '';
        $this->form->bin_no           = $s->bin_no ?? '';
        $this->form->notes            = $s->notes ?? '';
        $this->form->status           = $s->status_key; // 'active'|'inactive'|'blocked'

        // Documents proxy → keeps picker in sync
        $this->documents      = array_values(array_filter((array) ($s->documents ?? [])));
        $this->form->documents = $this->documents;

        $this->modalOpen = true;
    }

    private function resetModal(): void
    {
        $this->form->reset();
        $this->documents = [];
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

        if (DB::getSchemaBuilder()->hasTable('purchase_payables')) {
            $payable = (float) (DB::table('purchase_payables')->sum('due_amount') ?? 0);
            $advance = (float) (DB::table('purchase_payables')->sum('advance_amount') ?? 0);
        }

        if (DB::getSchemaBuilder()->hasTable('purchase_invoices')) {
            $invoices = (int) (DB::table('purchase_invoices')->count() ?? 0);
        }

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

        $hasPurchasePayables = DB::getSchemaBuilder()->hasTable('purchase_payables');
        $hasPurchaseInvoices = DB::getSchemaBuilder()->hasTable('purchase_invoices');

        $query = Supplier::query()
            ->search($this->search)
            ->statusKey($this->statusFilter === 'all' ? '' : $this->statusFilter);

        if ($hasPurchaseInvoices) {
            $query->withCount([
                'purchaseInvoices as purchase_invoices_count',
                'purchaseInvoices as unpaid_invoices_count' => fn ($q) => $q->where('status', '!=', 'paid'),
            ]);
        } else {
            $query->addSelect(DB::raw('0 as purchase_invoices_count, 0 as unpaid_invoices_count'));
        }

        if ($hasPurchasePayables) {
            $query->addSelect([
                'balance' => DB::table('purchase_payables')
                    ->selectRaw('COALESCE(SUM(advance_amount) - SUM(due_amount), 0)')
                    ->whereColumn('purchase_payables.supplier_id', 'suppliers.id'),
            ]);

            $query
                ->when($this->balanceFilter === 'due',     fn ($q) => $q->having('balance', '<', 0))
                ->when($this->balanceFilter === 'advance', fn ($q) => $q->having('balance', '>', 0))
                ->when($this->balanceFilter === 'settled', fn ($q) => $q->having('balance', '=', 0))
                ->when($this->sortBy === 'due',      fn ($q) => $q->orderBy('balance', 'asc'))
                ->when($this->sortBy === 'invoices', fn ($q) => $q->orderByDesc('purchase_invoices_count'));
        } else {
            $query->addSelect(DB::raw('0 as balance'));
        }

        $query
            ->when($this->sortBy === 'recent', fn ($q) => $q->latest())
            ->when($this->sortBy === 'name',   fn ($q) => $q->orderBy('name'));

        $suppliers = $query->paginate(15);

        return view('livewire.admin.supplier.supplier.supplier-list', [
            'suppliers' => $suppliers,
        ])->layout('layouts.admin.admin');
    }

    /* ───────────────────────────── Save (create or update) ────────────────── */
    public function save(): void
    {
        if ($this->editMode) {
            $this->authorizePermission('supplier.edit');
        } else {
            $this->authorizePermission('supplier.create');
        }

        $this->form->validate();

        if ($this->editMode && $this->editingId) {
            $s = Supplier::findOrFail($this->editingId);
            $s->update($this->form->toAttributes() + ['updated_by' => auth()->id()]);
            $label = "Supplier \"{$s->name}\" updated.";
        } else {
            $s = Supplier::create($this->form->toAttributes() + ['created_by' => auth()->id()]);
            $this->nextCode = (int) (Supplier::withTrashed()->max('id') ?? 0) + 1;
            $label = "Supplier \"{$s->name}\" created.";
        }

        $this->resetModal();
        $this->modalOpen = false;
        $this->editMode  = false;
        $this->editingId = null;

        $this->dispatch('toast', ['type' => 'success', 'message' => $label]);
    }

    public function closeModal(): void
    {
        $this->modalOpen = false;
        $this->editMode  = false;
        $this->editingId = null;
        $this->resetModal();
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
