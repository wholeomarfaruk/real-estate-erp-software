<?php

namespace App\Livewire\Admin\Properties;

use App\Models\Customer;
use App\Models\PaymentSchedule;
use App\Models\PropertySale;
use App\Models\PropertyUnit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class PropertySaleDetails extends Component
{
    public PropertySale $sale;

    // ── Sale edit drawer ─────────────────────────────────────────────────────
    public bool $drawerOpen = false;

    // ── Sale edit drawer fields ──────────────────────────────────────────────
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

    // ── Schedule drawer ──────────────────────────────────────────────────────
    public bool $scheduleDrawerOpen = false;
    public ?int $editingScheduleId  = null;
    public $sPaymentCategory = 'installment';
    public $sSequenceNo      = '';
    public $sDueDate         = '';
    public $sAmount          = '0';
    public $sPaidAmount      = '0';
    public $sStatus          = 'pending';
    public $sRemarks         = '';

    public function mount(PropertySale $sale): void
    {
        abort_unless(Auth::user()?->can('property_sale.view'), 403);
        $this->sale = $sale->load(['propertyUnit.property', 'customer', 'createdByUser', 'updatedByUser', 'paymentSchedules']);
    }

    // ── Financial auto-calculation ───────────────────────────────────────────
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

    // ── Drawer ───────────────────────────────────────────────────────────────
    public function openEdit(): void
    {
        abort_unless(Auth::user()?->can('property_sale.edit'), 403);

        $this->dPropertyUnitId      = (string) $this->sale->property_unit_id;
        $this->dCustomerId          = (string) $this->sale->customer_id;
        $this->dSaleDate            = $this->sale->sale_date?->format('Y-m-d') ?? '';
        $this->dContractDate        = $this->sale->contract_date?->format('Y-m-d') ?? '';
        $this->dSaleAmount          = (string) $this->sale->sale_amount;
        $this->dDiscountAmount      = (string) $this->sale->discount_amount;
        $this->dTaxAmount           = (string) $this->sale->tax_amount;
        $this->dNetAmount           = (string) $this->sale->net_amount;
        $this->dPaymentTerms        = (string) ($this->sale->payment_terms ?? '');
        $this->dPaymentStatus       = $this->sale->payment_status;
        $this->dStatus              = $this->sale->status;
        $this->dSalesRepresentative = $this->sale->sales_representative ?? '';
        $this->dNotes               = $this->sale->notes ?? '';

        $this->drawerOpen = true;
    }

    public function closeDrawer(): void
    {
        $this->drawerOpen = false;
        $this->resetValidation();
    }

    public function savePropertySale(): void
    {
        abort_unless(Auth::user()?->can('property_sale.edit'), 403);

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
            'dPaymentStatus.required'  => 'Payment status is required.',
            'dStatus.required'         => 'Status is required.',
        ]);

        if ($validator->fails()) {
            $this->setErrorBag($validator->errors());
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Please fix the validation errors.']);
            return;
        }

        $this->recalcNet();

        $this->sale->update([
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
        ]);

        $this->sale = $this->sale->fresh(['propertyUnit.property', 'customer', 'createdByUser', 'updatedByUser']);
        $this->closeDrawer();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Sale updated successfully.']);
    }

    // ── Schedule drawer ──────────────────────────────────────────────────────
    public function openAddSchedule(): void
    {
        abort_unless(Auth::user()?->can('property_sale.edit'), 403);
        $this->editingScheduleId = null;
        $this->sPaymentCategory  = 'installment';
        $this->sSequenceNo       = '';
        $this->sDueDate          = '';
        $this->sAmount           = '0';
        $this->sPaidAmount       = '0';
        $this->sStatus           = 'pending';
        $this->sRemarks          = '';
        $this->scheduleDrawerOpen = true;
    }

    public function openEditSchedule(int $id): void
    {
        abort_unless(Auth::user()?->can('property_sale.edit'), 403);
        $schedule = PaymentSchedule::findOrFail($id);
        $this->editingScheduleId = $id;
        $this->sPaymentCategory  = $schedule->payment_category;
        $this->sSequenceNo       = (string) ($schedule->sequence_no ?? '');
        $this->sDueDate          = $schedule->due_date->format('Y-m-d');
        $this->sAmount           = (string) $schedule->amount;
        $this->sPaidAmount       = (string) $schedule->paid_amount;
        $this->sStatus           = $schedule->status;
        $this->sRemarks          = $schedule->remarks ?? '';
        $this->scheduleDrawerOpen = true;
    }

    public function closeScheduleDrawer(): void
    {
        $this->scheduleDrawerOpen = false;
        $this->editingScheduleId  = null;
        $this->resetValidation();
    }

    public function saveSchedule(): void
    {
        abort_unless(Auth::user()?->can('property_sale.edit'), 403);

        $validator = Validator::make([
            'sPaymentCategory' => $this->sPaymentCategory,
            'sDueDate'         => $this->sDueDate,
            'sAmount'          => $this->sAmount,
        ], [
            'sPaymentCategory' => 'required|in:down_payment,installment,monthly_rent,security_deposit',
            'sDueDate'         => 'required|date',
            'sAmount'          => 'required|numeric|min:0',
        ], [
            'sPaymentCategory.required' => 'Payment category is required.',
            'sDueDate.required'         => 'Due date is required.',
            'sAmount.required'          => 'Amount is required.',
            'sAmount.numeric'           => 'Amount must be a number.',
        ]);

        if ($validator->fails()) {
            $this->setErrorBag($validator->errors());
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Please fix the validation errors.']);
            return;
        }

        $paid      = (float) $this->sPaidAmount;
        $total     = (float) $this->sAmount;
        $due       = round($total - $paid, 2);
        $autoStatus = $this->sStatus;
        if ($paid <= 0) {
            $autoStatus = 'pending';
        } elseif ($due <= 0) {
            $autoStatus = 'paid';
        } else {
            $autoStatus = 'partial';
        }

        $data = [
            'property_sale_id'  => $this->sale->id,
            'payment_category'  => $this->sPaymentCategory,
            'sequence_no'       => $this->sSequenceNo !== '' ? (int) $this->sSequenceNo : null,
            'due_date'          => $this->sDueDate,
            'amount'            => $total,
            'paid_amount'       => $paid,
            'due_amount'        => $due,
            'status'            => $autoStatus,
            'is_auto_generated' => false,
            'remarks'           => $this->sRemarks ?: null,
        ];

        if ($this->editingScheduleId) {
            PaymentSchedule::findOrFail($this->editingScheduleId)->update($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Schedule entry updated.']);
        } else {
            PaymentSchedule::create($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Schedule entry added.']);
        }

        $this->sale = $this->sale->fresh(['propertyUnit.property', 'customer', 'createdByUser', 'updatedByUser', 'paymentSchedules']);
        $this->closeScheduleDrawer();
    }

    public function deleteSchedule(int $id): void
    {
        abort_unless(Auth::user()?->can('property_sale.edit'), 403);
        PaymentSchedule::findOrFail($id)->delete();
        $this->sale = $this->sale->fresh(['propertyUnit.property', 'customer', 'createdByUser', 'updatedByUser', 'paymentSchedules']);
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Schedule entry removed.']);
    }

    public function markSchedulePaid(int $id): void
    {
        abort_unless(Auth::user()?->can('property_sale.edit'), 403);
        $schedule = PaymentSchedule::findOrFail($id);
        $schedule->update([
            'paid_amount' => $schedule->amount,
            'due_amount'  => 0,
            'status'      => 'paid',
        ]);
        $this->sale = $this->sale->fresh(['propertyUnit.property', 'customer', 'createdByUser', 'updatedByUser', 'paymentSchedules']);
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Marked as paid.']);
    }

    public function render()
    {
        abort_unless(Auth::user()?->can('property_sale.view'), 403);

        $units     = PropertyUnit::with('property')->orderBy('code')->get();
        $customers = Customer::where('status', 'active')->orderBy('name')->get();

        return view('livewire.admin.properties.property-sale-details', [
            'sale'      => $this->sale,
            'units'     => $units,
            'customers' => $customers,
        ])->layout('layouts.admin.admin');
    }
}
