<?php

namespace App\Livewire\Admin\Properties;

use App\Models\Customer;
use App\Models\PropertySale;
use App\Models\PropertyUnit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithPagination;

class PropertySaleList extends Component
{
    use WithPagination;

    // ── Filters ─────────────────────────────────────────────────────────────
    public string $search = '';
    public string $filterPaymentStatus = 'all';
    public string $filterStatus = 'all';

    // ── Drawer UI state ──────────────────────────────────────────────────────
    public bool $drawerOpen = false;
    public ?int $editingId = null;

    // ── Drawer fields ────────────────────────────────────────────────────────
    public $dPropertyUnitId = '';
    public $dCustomerId = '';
    public $dSaleDate = '';
    public $dContractDate = '';
    public $dSaleAmount = '0';
    public $dDiscountAmount = '0';
    public $dTaxAmount = '0';
    public $dNetAmount = '0';
    public $dPaymentTerms = '';
    public $dPaymentStatus = 'pending';
    public $dStatus = 'active';
    public $dSalesRepresentative = '';
    public $dNotes = '';

    // ── Lifecycle ────────────────────────────────────────────────────────────
    public function mount(): void
    {
        abort_unless(Auth::user()?->can('property_sale.view'), 403);
    }

    // ── Financial auto-calculation ───────────────────────────────────────────
    public function updatedDSaleAmount(): void
    {
        $this->recalcNet();
    }

    public function updatedDDiscountAmount(): void
    {
        $this->recalcNet();
    }

    public function updatedDTaxAmount(): void
    {
        $this->recalcNet();
    }

    public function recalcNet(): void
    {
        $this->dNetAmount = (string) round(
            (float) $this->dSaleAmount - (float) $this->dDiscountAmount + (float) $this->dTaxAmount,
            2
        );
    }

    // ── Drawer open/close ────────────────────────────────────────────────────
    public function openCreate(): void
    {
        abort_unless(Auth::user()?->can('property_sale.create'), 403);
        $this->resetDrawerFields();
        $this->editingId = null;
        $this->drawerOpen = true;
    }

    public function openEdit(int $id): void
    {
        abort_unless(Auth::user()?->can('property_sale.edit'), 403);

        $sale = PropertySale::findOrFail($id);
        $this->editingId = $id;

        $this->dPropertyUnitId    = (string) $sale->property_unit_id;
        $this->dCustomerId        = (string) $sale->customer_id;
        $this->dSaleDate          = $sale->sale_date?->format('Y-m-d') ?? '';
        $this->dContractDate      = $sale->contract_date?->format('Y-m-d') ?? '';
        $this->dSaleAmount        = (string) $sale->sale_amount;
        $this->dDiscountAmount    = (string) $sale->discount_amount;
        $this->dTaxAmount         = (string) $sale->tax_amount;
        $this->dNetAmount         = (string) $sale->net_amount;
        $this->dPaymentTerms      = (string) ($sale->payment_terms ?? '');
        $this->dPaymentStatus     = $sale->payment_status;
        $this->dStatus            = $sale->status;
        $this->dSalesRepresentative = $sale->sales_representative ?? '';
        $this->dNotes             = $sale->notes ?? '';

        $this->drawerOpen = true;
    }

    public function closeDrawer(): void
    {
        $this->drawerOpen = false;
        $this->editingId  = null;
        $this->resetValidation();
    }

    // ── Save ─────────────────────────────────────────────────────────────────
    public function savePropertySale(): void
    {
        if ($this->editingId) {
            abort_unless(Auth::user()?->can('property_sale.edit'), 403);
        } else {
            abort_unless(Auth::user()?->can('property_sale.create'), 403);
        }

        $validator = Validator::make([
            'dPropertyUnitId' => $this->dPropertyUnitId,
            'dCustomerId'     => $this->dCustomerId,
            'dSaleAmount'     => $this->dSaleAmount,
            'dPaymentStatus'  => $this->dPaymentStatus,
            'dStatus'         => $this->dStatus,
        ], [
            'dPropertyUnitId' => 'required|exists:property_units,id',
            'dCustomerId'     => 'required|exists:customers,id',
            'dSaleAmount'     => 'required|numeric|min:0',
            'dPaymentStatus'  => 'required|in:pending,partial,paid,cancelled',
            'dStatus'         => 'required|in:active,completed,cancelled,on_hold',
        ], [
            'dPropertyUnitId.required' => 'Please select a property unit.',
            'dPropertyUnitId.exists'   => 'Selected property unit does not exist.',
            'dCustomerId.required'     => 'Please select a customer.',
            'dCustomerId.exists'       => 'Selected customer does not exist.',
            'dSaleAmount.required'     => 'Sale amount is required.',
            'dSaleAmount.numeric'      => 'Sale amount must be a number.',
            'dSaleAmount.min'          => 'Sale amount must be at least 0.',
            'dPaymentStatus.required'  => 'Payment status is required.',
            'dPaymentStatus.in'        => 'Invalid payment status.',
            'dStatus.required'         => 'Status is required.',
            'dStatus.in'               => 'Invalid status.',
        ]);

        if ($validator->fails()) {
            $this->setErrorBag($validator->errors());
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Please fix the validation errors.']);
            return;
        }

        $this->recalcNet();

        $data = [
            'property_unit_id'    => $this->dPropertyUnitId,
            'customer_id'         => $this->dCustomerId,
            'sale_date'           => $this->dSaleDate ?: null,
            'contract_date'       => $this->dContractDate ?: null,
            'sale_amount'         => (float) $this->dSaleAmount,
            'discount_amount'     => (float) $this->dDiscountAmount,
            'tax_amount'          => (float) $this->dTaxAmount,
            'net_amount'          => (float) $this->dNetAmount,
            'payment_terms'       => $this->dPaymentTerms !== '' ? (int) $this->dPaymentTerms : null,
            'payment_status'      => $this->dPaymentStatus,
            'status'              => $this->dStatus,
            'sales_representative' => $this->dSalesRepresentative ?: null,
            'notes'               => $this->dNotes ?: null,
        ];

        if ($this->editingId) {
            $data['updated_by'] = Auth::id();
            PropertySale::findOrFail($this->editingId)->update($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Property sale updated successfully.']);
        } else {
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();
            PropertySale::create($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Property sale created successfully.']);
        }

        $this->closeDrawer();
    }

    // ── Delete ───────────────────────────────────────────────────────────────
    public function deletePropertySale(int $id): void
    {
        abort_unless(Auth::user()?->can('property_sale.delete'), 403);

        PropertySale::findOrFail($id)->delete();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Property sale deleted.']);
    }

    // ── Render ───────────────────────────────────────────────────────────────
    public function render()
    {
        abort_unless(Auth::user()?->can('property_sale.view'), 403);

        $query = PropertySale::with(['propertyUnit.property', 'customer'])
            ->when($this->search, function ($q) {
                $search = '%' . $this->search . '%';
                $q->where(function ($sub) use ($search) {
                    $sub->where('sale_number', 'like', $search)
                        ->orWhereHas('customer', fn($c) => $c->where('name', 'like', $search))
                        ->orWhereHas('propertyUnit', fn($u) => $u->where('code', 'like', $search));
                });
            })
            ->when($this->filterPaymentStatus !== 'all', fn($q) => $q->where('payment_status', $this->filterPaymentStatus))
            ->when($this->filterStatus !== 'all', fn($q) => $q->where('status', $this->filterStatus))
            ->latest();

        $sales = $query->paginate(15);

        $kpi = [
            'total'     => PropertySale::count(),
            'revenue'   => PropertySale::sum('net_amount'),
            'pending'   => PropertySale::where('payment_status', 'pending')->count(),
            'completed' => PropertySale::where('status', 'completed')->count(),
        ];

        $units     = PropertyUnit::with('property')->orderBy('code')->get();
        $customers = Customer::where('status', 'active')->orderBy('name')->get();

        return view('livewire.admin.properties.property-sale-list', compact('sales', 'kpi', 'units', 'customers'))
            ->layout('layouts.admin.admin');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────
    private function resetDrawerFields(): void
    {
        $this->dPropertyUnitId     = '';
        $this->dCustomerId         = '';
        $this->dSaleDate           = '';
        $this->dContractDate       = '';
        $this->dSaleAmount         = '0';
        $this->dDiscountAmount     = '0';
        $this->dTaxAmount          = '0';
        $this->dNetAmount          = '0';
        $this->dPaymentTerms       = '';
        $this->dPaymentStatus      = 'pending';
        $this->dStatus             = 'active';
        $this->dSalesRepresentative = '';
        $this->dNotes              = '';
    }
}
