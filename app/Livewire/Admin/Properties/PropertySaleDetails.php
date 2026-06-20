<?php

namespace App\Livewire\Admin\Properties;

use App\Models\Account;
use App\Models\Customer;
use App\Models\PaymentSchedule;
use App\Models\PropertySale;
use App\Models\PropertyUnit;
use App\Livewire\Traits\WithMediaPicker;
use App\Services\Property\PaymentAllocationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class PropertySaleDetails extends Component
{
    use WithMediaPicker;
    public PropertySale $sale;

    // ── Sale edit drawer ─────────────────────────────────────────────────────
    public bool $drawerOpen = false;

    // ── Sale edit drawer fields ──────────────────────────────────────────────
    public $dPropertyUnitId      = '';
    public $dCustomerId          = '';
    public $dSaleDate            = '';
    public $dContractDate        = '';
    public $dSaleAmount          = '0';
    public $dDiscountAmount      = '0';
    public $dTaxAmount           = '0';
    public $dNetAmount           = '0';
    public $dPaymentTerms        = '';
    public $dPaymentStatus       = 'pending';
    public $dStatus              = 'active';
    public $dSalesRepresentative = '';
    public $dNotes               = '';

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

    // ── Pay Now modal ────────────────────────────────────────────────────────
    public bool    $payNowModalOpen      = false;
    public bool    $receiptModalOpen     = false;
    public bool    $attachModalOpen      = false;
    public ?int    $payNowScheduleId     = null;
    public string  $payNowAccountType    = '';
    public string  $payNowAccountId      = '';
    public array   $payNowAccounts       = [];
    public string  $payNowPaymentMethod  = 'cash';
    public string  $payNowPayerName      = '';
    public string  $payNowReferenceNo    = '';
    public string  $payNowPhone          = '';
    public string  $payNowAmount         = '0';
    public string  $payNowDate           = '';
    public string  $payNowNotes          = '';
    public array   $payNowAttachmentIds  = [];
    public array   $payTransactions      = [];

    // ── Lifecycle ─────────────────────────────────────────────────────────────
    public function mount(PropertySale $sale): void
    {
        abort_unless(Auth::user()?->can('property_sale.view'), 403);
        $this->sale = $sale->load([
            'propertyUnit.property', 'saleUnits.propertyUnit.property', 'customer', 'createdByUser', 'updatedByUser',
            'paymentSchedules',
        ]);

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

    // ── Edit drawer ───────────────────────────────────────────────────────────
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

        $this->sale = $this->sale->fresh([
            'propertyUnit.property', 'saleUnits.propertyUnit.property', 'customer', 'createdByUser', 'updatedByUser',
            'paymentSchedules',
        ]);
        $this->closeDrawer();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Sale updated successfully.']);
    }

    // ── Schedule drawer ──────────────────────────────────────────────────────
    public function openAddSchedule(): void
    {
        abort_unless(Auth::user()?->can('property_sale.edit'), 403);
        $this->editingScheduleId  = null;
        $this->sPaymentCategory   = 'installment';
        $this->sSequenceNo        = '';
        $this->sDueDate           = '';
        $this->sAmount            = '0';
        $this->sPaidAmount        = '0';
        $this->sStatus            = 'pending';
        $this->sRemarks           = '';
        $this->scheduleDrawerOpen = true;
    }

    public function openEditSchedule(int $id): void
    {
        abort_unless(Auth::user()?->can('property_sale.edit'), 403);
        $schedule = PaymentSchedule::findOrFail($id);
        $this->editingScheduleId  = $id;
        $this->sPaymentCategory   = $schedule->payment_category;
        $this->sSequenceNo        = (string) ($schedule->sequence_no ?? '');
        $this->sDueDate           = $schedule->due_date->format('Y-m-d');
        $this->sAmount            = (string) $schedule->amount;
        $this->sPaidAmount        = (string) $schedule->paid_amount;
        $this->sStatus            = $schedule->status;
        $this->sRemarks           = $schedule->remarks ?? '';
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

        $paid       = (float) $this->sPaidAmount;
        $total      = (float) $this->sAmount;
        $due        = round($total - $paid, 2);
        $autoStatus = ($paid <= 0) ? 'pending' : (($due <= 0) ? 'paid' : 'partial');

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

        $this->sale = $this->sale->fresh([
            'propertyUnit.property', 'saleUnits.propertyUnit.property', 'customer', 'createdByUser', 'updatedByUser',
            'paymentSchedules',
        ]);
        $this->closeScheduleDrawer();
    }

    public function deleteSchedule(int $id): void
    {
        abort_unless(Auth::user()?->can('property_sale.edit'), 403);
        PaymentSchedule::findOrFail($id)->delete();
        $this->sale = $this->sale->fresh([
            'propertyUnit.property', 'saleUnits.propertyUnit.property', 'customer', 'createdByUser', 'updatedByUser',
            'paymentSchedules',
        ]);
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
        $this->sale = $this->sale->fresh([
            'propertyUnit.property', 'saleUnits.propertyUnit.property', 'customer', 'createdByUser', 'updatedByUser',
            'paymentSchedules',
        ]);
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Marked as paid.']);
    }

    // ── Pay Now modal ────────────────────────────────────────────────────────
    public function OpenPayNowModal(int $id): void
    {
        abort_unless(Auth::user()?->can('property_sale.edit'), 403);
        $schedule = PaymentSchedule::findOrFail($id);
        $this->payNowScheduleId    = $id;
        $this->payNowAccountType   = '';
        $this->payNowAccountId     = '';
        $this->payNowPaymentMethod = 'cash';
        $this->payNowPayerName     = '';
        $this->payNowReferenceNo   = '';
        $this->payNowPhone         = '';
        $this->payNowAmount        = (string) $schedule->due_amount;
        $this->payNowDate          = now()->format('Y-m-d H:i');
        $this->payNowNotes         = '';
        $this->payNowAttachmentIds = [];
        $this->payNowModalOpen     = true;
        $this->payTransactions     = $this->loadPayTransactions($schedule);
    }

    public function submitPayment(): void
    {
        abort_unless(Auth::user()?->can('property_sale.edit'), 403);

        $validator = Validator::make([
            'payNowAccountType'   => $this->payNowAccountType,
            'payNowAccountId'     => $this->payNowAccountId,
            'payNowPaymentMethod' => $this->payNowPaymentMethod,
            'payNowDate'          => $this->payNowDate,
            'payNowAmount'        => $this->payNowAmount,
        ], [
            'payNowAccountType'   => 'required',
            'payNowAccountId'     => 'required|exists:accounts,id',
            'payNowPaymentMethod' => 'required',
            'payNowDate'          => 'required|date',
            'payNowAmount'        => 'required|numeric|min:0.01',
        ], [
            'payNowAccountType.required'   => 'Receive account type is required.',
            'payNowAccountId.required'     => 'Please select an account.',
            'payNowAccountId.exists'       => 'Selected account does not exist.',
            'payNowPaymentMethod.required' => 'Payment method is required.',
            'payNowDate.required'          => 'Payment date is required.',
            'payNowAmount.required'        => 'Amount is required.',
            'payNowAmount.min'             => 'Amount must be greater than zero.',
        ]);

        if ($validator->fails()) {
            $this->setErrorBag($validator->errors());
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Please fix the validation errors.']);
            return;
        }

        $schedule = PaymentSchedule::findOrFail($this->payNowScheduleId);
        $amount   = round((float) $this->payNowAmount, 2);

        if ($amount > $schedule->due_amount) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Amount is greater than due amount.']);
            return;
        }

        // Auto-post the balanced double-entry via the configured accounting event:
        //   rent  → Dr [user payment account] / Cr Rent Revenue
        //   sale  → Dr [user payment account] / Cr Customer Advance
        $eventKey = $this->sale->sale_type === 'rent'
            ? 'property.rent_collection'
            : 'property.down_payment';

        $transaction = app(\App\Services\Accounts\PostingEngine::class)->record(
            $eventKey,
            new \App\Accounting\PostingContext(
                amount: $amount,
                datetime: $this->payNowDate,
                paymentAccountId: (int) $this->payNowAccountId,
                referenceType: PaymentSchedule::class,
                referenceId: (int) $schedule->id,
                referenceNo: $this->payNowReferenceNo ?: null,
                method: $this->payNowPaymentMethod,
                name: $this->payNowPayerName ?: null,
                phone: $this->payNowPhone ?: null,
                notes: $this->payNowNotes ?: null,
                actorId: Auth::id(),
            ),
        );

        if (! empty($this->payNowAttachmentIds)) {
            $transaction->update(['attachments' => $this->payNowAttachmentIds]);
        }

        $newPaid = round((float) $schedule->paid_amount + $amount, 2);
        $newDue  = round(max(0, (float) $schedule->amount - $newPaid), 2);
        $schedule->update([
            'paid_amount' => $newPaid,
            'due_amount'  => $newDue,
            'status'      => $newPaid <= 0 ? 'pending' : ($newDue <= 0 ? 'paid' : 'partial'),
        ]);

        app(PaymentAllocationService::class)->syncSalePaymentStatus($this->sale);

        $this->payTransactions = $this->loadPayTransactions($schedule);

        $this->sale = $this->sale->fresh([
            'propertyUnit.property', 'saleUnits.propertyUnit.property', 'customer', 'createdByUser', 'updatedByUser',
            'paymentSchedules',
        ]);

        $this->payNowAmount        = (string) $newDue;
        $this->payNowNotes         = '';
        $this->payNowAttachmentIds = [];
        $this->resetValidation();

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Payment recorded successfully.']);
    }

    /**
     * Map a schedule's payment transactions to the array shape the view renders.
     * Under double-entry the amount + receiving account come from the debit line
     * (the money-in side), not the removed transaction header columns.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function loadPayTransactions(PaymentSchedule $schedule): array
    {
        return $schedule->paymentTransactions()
            ->with('lines.account:id,name,code')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($tx): array {
                $debitLine = $tx->lines->firstWhere(fn ($l) => (float) $l->debit > 0) ?? $tx->lines->first();

                return [
                    'id'      => $tx->id,
                    'debit'   => (float) $tx->lines->sum('debit'),
                    'method'  => $tx->method,
                    'name'    => $tx->name,
                    'notes'   => $tx->notes,
                    'datetime' => optional($tx->datetime)->toDateTimeString(),
                    'created_at' => optional($tx->created_at)->toDateTimeString(),
                    'account' => $debitLine?->account
                        ? ['name' => $debitLine->account->name, 'code' => $debitLine->account->code]
                        : null,
                ];
            })
            ->all();
    }


    public function closePayNowModal(): void
    {
        $this->payNowModalOpen     = false;
        $this->payNowScheduleId    = null;
        $this->payNowNotes         = '';
        $this->payNowAttachmentIds = [];
        $this->payTransactions     = [];
        $this->resetValidation();
    }

    public function updatedPayNowAccountType(): void
    {
        $this->payNowAccountId  = '';
        $this->payNowAccounts   = $this->payNowAccountType
            ? Account::where('is_active', true)
                ->where('sub_type', $this->payNowAccountType)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->toArray()
            : [];
    }

    // ── Render ─────────────────────────────────────────────────────────────────
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
