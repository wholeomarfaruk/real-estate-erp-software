<?php

namespace App\Livewire\Admin\Properties;

use App\Models\Customer;
use App\Models\Property;
use App\Models\PropertySale;
use App\Models\PropertySaleUnit;
use App\Models\PropertyUnit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithPagination;

class PropertySaleList extends Component
{
    use WithPagination;

    // ── Filters ──────────────────────────────────────────────────────────────
    public string $search              = '';
    public string $filterPaymentStatus = 'all';
    public string $filterStatus        = 'all';

    // ── Edit drawer UI state ──────────────────────────────────────────────────
    public bool $drawerOpen = false;
    public ?int $editingId  = null;
    public array $editingUnits = [];

    // ── Edit drawer fields ────────────────────────────────────────────────────
    public $dPropertyId         = '';
    public $dSaleType           = 'sale';
    public $dPropertyUnitId     = '';
    public $dCustomerId         = '';
    public $dSaleDate           = '';
    public $dContractDate       = '';
    public $dSaleAmount         = '0';
    public $dDiscountAmount     = '0';
    public $dTaxAmount          = '0';
    public $dNetAmount          = '0';
    public $dPaymentTerms       = '';
    public $dPaymentStatus      = 'pending';
    public $dStatus             = 'active';
    public $dSalesRepresentative = '';
    public $dNotes              = '';

    // ── Rent-specific fields ───────────────────────────────────────────────────
    public $dRentStartDate      = '';
    public $dRentEndDate        = '';
    public $dSecurityDeposit    = '0';
    public $dIsRenewal          = false;
    public $dRenewalDate        = '';

    // ── Lifecycle ─────────────────────────────────────────────────────────────
    public function mount(): void
    {
        abort_unless(Auth::user()?->can('property_sale.view'), 403);
    }

    // ── Reactive ──────────────────────────────────────────────────────────────
    public function updatedDPropertyId(): void
    {
        $this->dPropertyUnitId = '';
    }

    public function updatedDSaleAmount(): void    { $this->recalcNet(); }
    public function updatedDDiscountAmount(): void { $this->recalcNet(); }
    public function updatedDTaxAmount(): void      { $this->recalcNet(); }

    public function recalcNet(): void
    {
        $this->dNetAmount = (string) round(
            (float) $this->dSaleAmount - (float) $this->dDiscountAmount + (float) $this->dTaxAmount,
            2
        );
    }

    // ── Delete validation ──────────────────────────────────────────────────────
    #[\Livewire\Attributes\Computed]
    public function canDeleteSale(int $id): bool
    {
        $sale = PropertySale::findOrFail($id);

        // Cannot delete if handed over (unless superadmin)
        if ($sale->isHandedOver() && ! Auth::user()?->hasRole('superadmin')) {
            return false;
        }

        // Cannot delete if any payment has been made
        $totalPaidAmount = (float) $sale->paymentSchedules()->sum('paid_amount');
        return $totalPaidAmount <= 0;
    }

    public function getDeleteValidation(int $id): array
    {
        abort_unless(Auth::user()?->can('property_sale.delete'), 403);

        $sale = PropertySale::findOrFail($id);

        // Check if handed over
        if ($sale->isHandedOver() && ! Auth::user()?->hasRole('superadmin')) {
            return [
                'canDelete' => false,
                'type'      => 'error',
                'title'     => 'Cannot Delete',
                'text'      => 'This sale has been handed over and can no longer be deleted.',
            ];
        }

        // Check if any payment has been made
        $totalPaidAmount = (float) $sale->paymentSchedules()->sum('paid_amount');

        if ($totalPaidAmount > 0) {
            return [
                'canDelete' => false,
                'type'      => 'error',
                'title'     => 'Cannot Delete',
                'text'      => "This sale cannot be deleted because ৳ " . number_format($totalPaidAmount, 2) . " has already been paid. Please reverse the payment first.",
            ];
        }

        return [
            'canDelete' => true,
            'type'      => 'warning',
            'title'     => 'Delete Property Sale?',
            'text'      => 'Are you sure you want to delete this property sale? This action cannot be undone.',
        ];
    }

    // ── Edit drawer ───────────────────────────────────────────────────────────
    public function openEdit(int $id): void
    {
        abort_unless(Auth::user()?->can('property_sale.edit'), 403);

        $sale = PropertySale::with('saleUnits.propertyUnit')->findOrFail($id);
        $this->editingId = $id;

        $this->dPropertyId          = (string) ($sale->property_id ?? $sale->propertyUnit?->property_id ?? '');
        $this->dSaleType            = $sale->sale_type ?? 'sale';
        $this->dPropertyUnitId      = (string) $sale->property_unit_id;
        $this->dCustomerId          = (string) $sale->customer_id;
        $this->dSaleDate            = $sale->sale_date?->format('Y-m-d') ?? '';
        $this->dContractDate        = $sale->contract_date?->format('Y-m-d') ?? '';
        $this->dSaleAmount          = (string) $sale->sale_amount;
        $this->dDiscountAmount      = (string) $sale->discount_amount;
        $this->dTaxAmount           = (string) $sale->tax_amount;
        $this->dNetAmount           = (string) $sale->net_amount;
        $this->dPaymentTerms        = (string) ($sale->payment_terms ?? '');
        $this->dPaymentStatus       = $sale->payment_status;
        $this->dStatus              = $sale->status;
        $this->dSalesRepresentative = $sale->sales_representative ?? '';
        $this->dNotes               = $sale->notes ?? '';

        // Rent-specific fields
        $this->dRentStartDate       = $sale->rent_start_date?->format('Y-m-d') ?? '';
        $this->dRentEndDate         = $sale->rent_end_date?->format('Y-m-d') ?? '';
        $this->dSecurityDeposit     = (string) $sale->security_deposit_amount;
        $this->dIsRenewal           = (bool) $sale->is_renewal;
        $this->dRenewalDate         = $sale->renewal_date?->format('Y-m-d') ?? '';

        $this->editingUnits = $sale->saleUnits->map(fn($unit) => [
            'id'               => $unit->id,
            'property_unit_id' => $unit->property_unit_id,
            'unit_code'        => $unit->propertyUnit?->code ?? '',
            'sale_amount'      => (string) $unit->sale_amount,
            'discount_amount'  => (string) $unit->discount_amount,
            'tax_amount'       => (string) $unit->tax_amount,
            'net_amount'       => (string) $unit->net_amount,
            'service_charge'   => (string) $unit->service_charge,
            'utility_charge'   => (string) $unit->utility_charge,
        ])->toArray();

        $this->drawerOpen = true;
    }

    public function closeDrawer(): void
    {
        $this->drawerOpen = false;
        $this->editingId  = null;
        $this->resetValidation();
    }

    // ── Save (edit only) ─────────────────────────────────────────────────────
    public function savePropertySale(): void
    {
        abort_unless(Auth::user()?->can('property_sale.edit'), 403);

        $validator = Validator::make([
            'dSaleType'       => $this->dSaleType,
            'dPropertyId'     => $this->dPropertyId,
            'dPropertyUnitId' => $this->dPropertyUnitId,
            'dCustomerId'     => $this->dCustomerId,
            'dSaleAmount'     => $this->dSaleAmount,
            'dPaymentStatus'  => $this->dPaymentStatus,
            'dStatus'         => $this->dStatus,
        ], [
            'dSaleType'       => 'required|in:sale,rent',
            'dPropertyId'     => 'required|exists:properties,id',
            'dPropertyUnitId' => 'required|exists:property_units,id',
            'dCustomerId'     => 'required|exists:customers,id',
            'dSaleAmount'     => 'required|numeric|min:0',
            'dPaymentStatus'  => 'required|in:pending,partial,paid,cancelled',
            'dStatus'         => 'required|in:active,completed,cancelled,on_hold',
        ], [
            'dSaleType.required'       => 'Please select a sale type.',
            'dPropertyId.required'     => 'Please select a property.',
            'dPropertyId.exists'       => 'Selected property does not exist.',
            'dPropertyUnitId.required' => 'Please select a property unit.',
            'dPropertyUnitId.exists'   => 'Selected property unit does not exist.',
            'dCustomerId.required'     => 'Please select a customer.',
            'dCustomerId.exists'       => 'Selected customer does not exist.',
            'dSaleAmount.required'     => 'Sale amount is required.',
            'dSaleAmount.numeric'      => 'Sale amount must be a number.',
            'dPaymentStatus.required'  => 'Payment status is required.',
            'dStatus.required'         => 'Status is required.',
        ]);

        if ($validator->fails()) {
            $this->setErrorBag($validator->errors());
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Please fix the validation errors.']);
            return;
        }

        $this->recalcNet();

        $sale = PropertySale::findOrFail($this->editingId);
        $updateData = [
            'property_id'          => $this->dPropertyId,
            'sale_type'            => $this->dSaleType,
            'property_unit_id'     => $this->dPropertyUnitId,
            'customer_id'          => $this->dCustomerId,
            'sale_date'            => $this->dSaleDate ?: null,
            'contract_date'        => $this->dContractDate ?: null,
            'sale_amount'          => (float) $this->dSaleAmount,
            'discount_amount'      => (float) $this->dDiscountAmount,
            'tax_amount'           => (float) $this->dTaxAmount,
            'net_amount'           => (float) $this->dNetAmount,
            'payment_terms'        => $this->dPaymentTerms !== '' ? (int) $this->dPaymentTerms : null,
            'payment_status'       => $this->dPaymentStatus,
            'status'               => $this->dStatus,
            'sales_representative' => $this->dSalesRepresentative ?: null,
            'notes'                => $this->dNotes ?: null,
            'updated_by'           => Auth::id(),
        ];

        if ($this->dSaleType === 'rent') {
            $updateData['rent_start_date'] = $this->dRentStartDate ?: null;
            $updateData['rent_end_date'] = $this->dRentEndDate ?: null;
            $updateData['security_deposit_amount'] = (float) $this->dSecurityDeposit;
            $updateData['is_renewal'] = $this->dIsRenewal;
            $updateData['renewal_date'] = $this->dRenewalDate && $this->dIsRenewal ? $this->dRenewalDate : null;
        }

        $sale->update($updateData);

        $totalSaleAmount = 0;
        $totalDiscountAmount = 0;
        $totalTaxAmount = 0;
        $totalNetAmount = 0;

        foreach ($this->editingUnits as $unitData) {
            $unitNetAmount = (float) $unitData['sale_amount'] - (float) $unitData['discount_amount'] + (float) $unitData['tax_amount'] + (float) $unitData['service_charge'] + (float) $unitData['utility_charge'];

            PropertySaleUnit::findOrFail($unitData['id'])->update([
                'sale_amount'     => (float) $unitData['sale_amount'],
                'discount_amount' => (float) $unitData['discount_amount'],
                'tax_amount'      => (float) $unitData['tax_amount'],
                'service_charge'  => (float) $unitData['service_charge'],
                'utility_charge'  => (float) $unitData['utility_charge'],
                'net_amount'      => round($unitNetAmount, 2),
            ]);

            $totalSaleAmount += (float) $unitData['sale_amount'];
            $totalDiscountAmount += (float) $unitData['discount_amount'];
            $totalTaxAmount += (float) $unitData['tax_amount'];
            $totalNetAmount += round($unitNetAmount, 2);
        }

        $sale->update([
            'sale_amount'   => round($totalSaleAmount, 2),
            'discount_amount' => round($totalDiscountAmount, 2),
            'tax_amount'    => round($totalTaxAmount, 2),
            'net_amount'    => round($totalNetAmount, 2),
        ]);

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Property sale updated successfully.']);
        $this->closeDrawer();
    }

    // ── Delete ────────────────────────────────────────────────────────────────
    public function confirmDelete(int $id): void
    {
        abort_unless(Auth::user()?->can('property_sale.delete'), 403);

        $validation = $this->getDeleteValidation($id);

        if (!$validation['canDelete']) {
            // Cannot delete - dispatch error alert
            $this->dispatch('swal-error', [
                'title' => $validation['title'],
                'text'  => $validation['text'],
            ]);
            return;
        }

        // Can delete - dispatch confirmation alert
        $this->dispatch('swal-confirm', [
            'method'=> 'deletePropertySaleConfirmed',
            'id'    => $id,
            'title' => $validation['title'],
            'text'  => $validation['text'],
        ]);
    }

    public function deletePropertySaleConfirmed(int $id): void
    {
        abort_unless(Auth::user()?->can('property_sale.delete'), 403);

        $sale = PropertySale::findOrFail($id);
        $sale->delete();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Property sale deleted successfully.']);
    }

    public function deletePropertySale(int $id): void
    {
        abort_unless(Auth::user()?->can('property_sale.delete'), 403);

        $validation = $this->getDeleteValidation($id);

        if (!$validation['canDelete']) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $validation['text']]);
            return;
        }

        PropertySale::findOrFail($id)->delete();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Property sale deleted successfully.']);
    }

    // ── Render ────────────────────────────────────────────────────────────────
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

        $properties = Property::orderBy('name')->get(['id', 'name', 'code']);

        $units = $this->dPropertyId
            ? PropertyUnit::where('property_id', $this->dPropertyId)->orderBy('code')->get()
            : collect();

        $customers = Customer::where('status', 'active')->orderBy('name')->get();

        return view('livewire.admin.properties.property-sale-list',
            compact('sales', 'kpi', 'properties', 'units', 'customers'))
            ->layout('layouts.admin.admin');
    }
}
