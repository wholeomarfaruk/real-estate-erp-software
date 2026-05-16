<?php

namespace App\Livewire\Admin\Properties;

use App\Models\Customer;
use App\Models\Property;
use App\Models\PropertySale;
use App\Models\PropertyUnit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class PropertySaleCreate extends Component
{
    public $dSaleType           = 'property_sale';
    public $dPropertyId         = '';
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

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('property_sale.create'), 403);
        $this->dSaleDate = now()->format('Y-m-d');
    }

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

    public function save(): void
    {
        abort_unless(Auth::user()?->can('property_sale.create'), 403);

        $validator = Validator::make([
            'dSaleType'       => $this->dSaleType,
            'dPropertyId'     => $this->dPropertyId,
            'dPropertyUnitId' => $this->dPropertyUnitId,
            'dCustomerId'     => $this->dCustomerId,
            'dSaleAmount'     => $this->dSaleAmount,
            'dPaymentStatus'  => $this->dPaymentStatus,
            'dStatus'         => $this->dStatus,
        ], [
            'dSaleType'       => 'required|in:property_sale,land_share,rent',
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

        $sale = PropertySale::create([
            'sale_type'            => $this->dSaleType,
            'property_id'          => $this->dPropertyId,
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
            'created_by'           => Auth::id(),
            'updated_by'           => Auth::id(),
        ]);

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Property sale created successfully.']);
        $this->redirect(route('admin.properties.sales.show', $sale), navigate: true);
    }

    public function render()
    {
        abort_unless(Auth::user()?->can('property_sale.create'), 403);

        $properties = Property::orderBy('name')->get(['id', 'name', 'code']);

        $units = $this->dPropertyId
            ? PropertyUnit::where('property_id', $this->dPropertyId)->orderBy('code')->get()
            : collect();

        $customers = Customer::where('status', 'active')->orderBy('name')->get();

        return view('livewire.admin.properties.property-sale-create',
            compact('properties', 'units', 'customers'))
            ->layout('layouts.admin.admin');
    }
}
