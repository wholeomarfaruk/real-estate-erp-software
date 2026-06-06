<?php

namespace App\Livewire\Admin\Properties;

use App\Models\Customer;
use App\Models\PaymentSchedule;
use App\Models\Property;
use App\Models\PropertySale;
use App\Models\PropertyUnit;
use App\Services\Property\ScheduleGeneratorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class PropertySaleCreate extends Component
{
    // ── Core ─────────────────────────────────────────────────────────────────
    public string $dSaleType          = 'sale';
    public string $dPropertyId        = '';
    public string $dPropertyUnitId    = '';
    public string $dCustomerId        = '';
    public string $dSaleDate          = '';
    public string $dContractDate      = '';

    // ── Sale Financial ────────────────────────────────────────────────────────
    public string $dSaleAmount         = '0';
    public string $dDiscountAmount     = '0';
    public string $dTaxAmount          = '0';
    public string $dNetAmount          = '0';
    public string $dDownPaymentAmount      = '0';
    public string $dDownPaymentPercentage = '0';
    public string $dPaymentTerms          = '';

    // ── Service Charge (both types) ──────────────────────────────────────────
    public string $dServiceCharge          = '0';

    // ── Rent ─────────────────────────────────────────────────────────────────
    public string $dRentStartDate          = '';
    public string $dRentEndDate            = '';
    public string $dSecurityDepositAmount  = '0';
    public bool   $dIsRenewal              = false;
    public string $dRenewalDate            = '';

    // ── Schedule ─────────────────────────────────────────────────────────────
    public bool   $dIsScheduled        = false;
    public string $dScheduleType       = 'monthly';
    public string $dScheduleDay        = '5';
    public string $dScheduleStartDate  = '';
    public string $dScheduleCount      = '';
    public string $dScheduleAmount     = '0';
    public array  $schedulePreview     = [];

    // ── Status & Meta ────────────────────────────────────────────────────────
    public string $dPaymentStatus       = 'pending';
    public string $dStatus              = 'active';
    public string $dSalesRepresentative = '';
    public string $dNotes               = '';

    // ── Lifecycle ─────────────────────────────────────────────────────────────
    public function mount(): void
    {
        abort_unless(Auth::user()?->can('property_sale.create'), 403);
        $this->dSaleDate = now()->format('Y-m-d');
    }

    // ── Reactive hooks ────────────────────────────────────────────────────────
    public function updatedDPropertyId(): void
    {
        $this->dPropertyUnitId        = '';
        $this->dSaleAmount            = '0';
        $this->dDownPaymentPercentage = '0';
        $this->dDownPaymentAmount     = '0';
        $this->dServiceCharge         = '0';
        $this->recalcNet();
    }

    public function updatedDPropertyUnitId(): void
    {
        if (!$this->dPropertyUnitId) return;

        $unit = PropertyUnit::find($this->dPropertyUnitId);
        if (!$unit) return;

        if ($this->dSaleType === 'sale') {
            $price = (float) ($unit->price ?: $unit->sell_price ?: 0);
            if ($price > 0) $this->dSaleAmount = (string) $price;

            if ($unit->down_payment_percentage) {
                $this->dDownPaymentPercentage = (string) (float) $unit->down_payment_percentage;
            }

            $this->dServiceCharge = (string) (float) ($unit->service_charge ?? 0);

            $this->recalcNet();
            $this->recalcDownPaymentFromPercentage();
            $this->recalcScheduleAmount();
            if ($this->dIsScheduled) $this->generateSchedulePreview();

        } elseif ($this->dSaleType === 'rent') {
            if ($unit->deposit_amount) {
                $this->dSecurityDepositAmount = (string) (float) $unit->deposit_amount;
            }
            $rentAmount = (float) ($unit->rent_amount ?: $unit->price ?: 0);
            if ($rentAmount > 0) $this->dScheduleAmount = (string) $rentAmount;
            $this->dServiceCharge = (string) (float) ($unit->service_charge ?? 0);
            if ($this->dIsScheduled) $this->generateSchedulePreview();
        }
    }

    public function updatedDSaleAmount(): void
    {
        $this->recalcNet();
        $this->recalcDownPaymentFromPercentage();
        $this->recalcScheduleAmount();
        if ($this->dIsScheduled) $this->generateSchedulePreview();
    }

    public function updatedDDiscountAmount(): void
    {
        $this->recalcNet();
        $this->recalcDownPaymentFromPercentage();
        $this->recalcScheduleAmount();
        if ($this->dIsScheduled) $this->generateSchedulePreview();
    }

    public function updatedDTaxAmount(): void
    {
        $this->recalcNet();
        $this->recalcDownPaymentFromPercentage();
        $this->recalcScheduleAmount();
        if ($this->dIsScheduled) $this->generateSchedulePreview();
    }

    public function updatedDDownPaymentPercentage(): void
    {
        $this->recalcDownPaymentFromPercentage();
        $this->recalcScheduleAmount();
        if ($this->dIsScheduled) $this->generateSchedulePreview();
    }

    public function updatedDDownPaymentAmount(): void
    {
        $this->recalcPercentageFromDownPayment();
        $this->recalcScheduleAmount();
        if ($this->dIsScheduled) $this->generateSchedulePreview();
    }

    public function updatedDSaleType(): void
    {
        $this->schedulePreview        = [];
        $this->dPropertyUnitId        = '';
        $this->dDownPaymentPercentage = '0';
        $this->dDownPaymentAmount     = '0';
        $this->dSecurityDepositAmount = '0';
        $this->dScheduleAmount        = '0';
        $this->dServiceCharge         = '0';
        $this->recalcNet();
    }

    public function updatedDIsScheduled(): void
    {
        if (!$this->dIsScheduled) {
            $this->schedulePreview = [];
        } else {
            $this->recalcScheduleAmount();
            $this->generateSchedulePreview();
        }
    }

    public function updatedDScheduleType(): void       { $this->generateSchedulePreview(); }
    public function updatedDScheduleDay(): void        { $this->generateSchedulePreview(); }
    public function updatedDScheduleStartDate(): void  { $this->generateSchedulePreview(); }
    public function updatedDScheduleCount(): void
    {
        $this->recalcScheduleAmount();
        $this->generateSchedulePreview();
    }
    public function updatedDScheduleAmount(): void     { $this->generateSchedulePreview(); }

    // ── Helpers ───────────────────────────────────────────────────────────────
    public function recalcNet(): void
    {
        $this->dNetAmount = (string) round(
            (float) $this->dSaleAmount - (float) $this->dDiscountAmount + (float) $this->dTaxAmount,
            2
        );
    }

    protected function recalcDownPaymentFromPercentage(): void
    {
        $net = (float) $this->dNetAmount;
        $pct = (float) $this->dDownPaymentPercentage;
        $this->dDownPaymentAmount = ($net > 0 && $pct > 0)
            ? (string) round($net * $pct / 100, 2)
            : '0';
    }

    protected function recalcPercentageFromDownPayment(): void
    {
        $net = (float) $this->dNetAmount;
        $dp  = (float) $this->dDownPaymentAmount;
        $this->dDownPaymentPercentage = $net > 0
            ? (string) round($dp / $net * 100, 2)
            : '0';
    }

    protected function recalcScheduleAmount(): void
    {
        if (!$this->dIsScheduled) return;
        $count = (int) $this->dScheduleCount;
        if ($count <= 0) return;
        $remaining = max(0.0, (float) $this->dNetAmount - (float) $this->dDownPaymentAmount);
        $this->dScheduleAmount = (string) round($remaining / $count, 2);
    }

    public function generateSchedulePreview(): void
    {
        if (!$this->dIsScheduled || !$this->dScheduleStartDate || !$this->dScheduleCount || (int) $this->dScheduleCount <= 0) {
            $this->schedulePreview = [];
            return;
        }

        $dates = app(ScheduleGeneratorService::class)->previewDates(
            $this->dScheduleType,
            $this->dScheduleDay,
            $this->dScheduleStartDate,
            (int) $this->dScheduleCount,
        );

        $category = $this->dSaleType === 'rent' ? 'Monthly Rent' : 'Installment';
        $amount   = (float) $this->dScheduleAmount;

        $this->schedulePreview = array_map(fn ($date, $i) => [
            'seq'      => $i + 1,
            'label'    => $category . ' #' . str_pad($i + 1, 2, '0', STR_PAD_LEFT),
            'due_date' => $date,
            'amount'   => $amount,
        ], $dates, array_keys($dates));
    }

    // ── Save ─────────────────────────────────────────────────────────────────
    public function save(): void
    {
        abort_unless(Auth::user()?->can('property_sale.create'), 403);

        $rules = [
            'dSaleType'       => 'required|in:sale,rent',
            'dPropertyId'     => 'required|exists:properties,id',
            'dPropertyUnitId' => 'required|exists:property_units,id',
            'dCustomerId'     => 'required|exists:customers,id',
        ];

        if ($this->dSaleType === 'sale') {
            $rules['dSaleAmount'] = 'required|numeric|min:0';
        }

        if ($this->dIsScheduled) {
            $rules['dScheduleCount']      = 'required|integer|min:1|max:360';
            $rules['dScheduleAmount']     = 'required|numeric|min:0';
            $rules['dScheduleStartDate']  = 'required|date';
            $rules['dScheduleDay']        = 'required|integer|min:1|max:28';
        }

        $validator = Validator::make([
            'dSaleType'          => $this->dSaleType,
            'dPropertyId'        => $this->dPropertyId,
            'dPropertyUnitId'    => $this->dPropertyUnitId,
            'dCustomerId'        => $this->dCustomerId,
            'dSaleAmount'        => $this->dSaleAmount,
            'dScheduleCount'     => $this->dScheduleCount,
            'dScheduleAmount'    => $this->dScheduleAmount,
            'dScheduleStartDate' => $this->dScheduleStartDate,
            'dScheduleDay'       => $this->dScheduleDay,
        ], $rules, [
            'dSaleType.required'       => 'Please select a sale type.',
            'dPropertyId.required'     => 'Please select a property.',
            'dPropertyUnitId.required' => 'Please select a property unit.',
            'dCustomerId.required'     => 'Please select a customer.',
            'dSaleAmount.required'     => 'Sale amount is required.',
            'dScheduleCount.required'  => 'Schedule count is required.',
            'dScheduleAmount.required' => 'Schedule amount is required.',
            'dScheduleStartDate.required' => 'Schedule start date is required.',
        ]);

        if ($validator->fails()) {
            $this->setErrorBag($validator->errors());
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Please fix the validation errors.']);
            return;
        }

        $this->recalcNet();

        $sale = PropertySale::create([
            'sale_type'               => $this->dSaleType,
            'property_id'             => $this->dPropertyId,
            'property_unit_id'        => $this->dPropertyUnitId,
            'customer_id'             => $this->dCustomerId,
            'sale_date'               => $this->dSaleDate ?: null,
            'contract_date'           => $this->dContractDate ?: null,
            // sale financials
            'sale_amount'             => (float) $this->dSaleAmount,
            'discount_amount'         => (float) $this->dDiscountAmount,
            'tax_amount'              => (float) $this->dTaxAmount,
            'net_amount'              => (float) $this->dNetAmount,
            'down_payment_amount'      => (float) $this->dDownPaymentAmount,
            'down_payment_percentage'  => (float) $this->dDownPaymentPercentage ?: null,
            'payment_terms'            => $this->dPaymentTerms !== '' ? (int) $this->dPaymentTerms : null,
            // rent
            'rent_start_date'         => $this->dRentStartDate ?: null,
            'rent_end_date'           => $this->dRentEndDate ?: null,
            'security_deposit_amount' => (float) $this->dSecurityDepositAmount,
            'is_renewal'              => $this->dIsRenewal,
            'renewal_date'            => $this->dRenewalDate ?: null,
            // schedule
            'is_scheduled'            => $this->dIsScheduled,
            'schedule_type'           => $this->dIsScheduled ? $this->dScheduleType : null,
            'schedule_day'            => $this->dIsScheduled ? $this->dScheduleDay : null,
            'schedule_start_date'     => $this->dIsScheduled && $this->dScheduleStartDate ? $this->dScheduleStartDate : null,
            'schedule_count'          => $this->dIsScheduled ? (int) $this->dScheduleCount : null,
            'schedule_amount'         => $this->dIsScheduled ? (float) $this->dScheduleAmount : 0,
            'schedule_name'           => $this->dIsScheduled
                ? ($this->dSaleType === 'rent' ? 'Monthly Rent' : 'Monthly Installment')
                : null,
            'schedule_status'         => $this->dIsScheduled ? 'active' : null,
            // meta
            'payment_status'          => $this->dPaymentStatus,
            'status'                  => $this->dStatus,
            'sales_representative'    => $this->dSalesRepresentative ?: null,
            'notes'                   => $this->dNotes ?: null,
            'created_by'              => Auth::id(),
            'updated_by'              => Auth::id(),
        ]);

        // Generate payment schedules
        app(ScheduleGeneratorService::class)->generateForSale($sale, (float) $this->dServiceCharge);

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Property sale created successfully.']);
        $this->redirect(route('admin.properties.sales.show', $sale), navigate: true);
    }

    // ── Render ────────────────────────────────────────────────────────────────
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
