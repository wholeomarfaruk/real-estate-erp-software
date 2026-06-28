<?php

namespace App\Livewire\Admin\Properties;

use App\Models\Customer;
use App\Models\PaymentSchedule;
use App\Models\Property;
use App\Models\PropertySale;
use App\Models\PropertySaleUnit;
use App\Models\PropertyUnit;
use App\Services\Property\ScheduleGeneratorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class PropertySaleCreate extends Component
{
    // ── Core ─────────────────────────────────────────────────────────────────
    public string $dSaleType          = 'sale';
    public string $dPropertyId        = '';
    public string $dPropertyUnitId    = '';   // RENT path only (single unit)
    public string $dCustomerId        = '';
    public string $dSaleDate          = '';
    public string $dContractDate      = '';

    /**
     * SALE path only — one row per unit on the invoice. Each row:
     *   property_unit_id, sale_amount, discount_amount, tax_amount,
     *   service_charge, down_payment_percentage, net_amount (derived)
     *
     * @var array<int, array<string, string>>
     */
    public array $dUnits = [];

    // ── Sale Financial ────────────────────────────────────────────────────────
    public string $dSaleAmount         = '0';
    public string $dDiscountAmount     = '0';
    public string $dTaxAmount          = '0';
    public string $dNetAmount          = '0';
    public string $dDownPaymentAmount      = '0';
    public string $dDownPaymentPercentage = '0';
    public string $dPaymentTerms          = '';

    // ── Service / Utility Charge (both types) ────────────────────────────────
    public string $dServiceCharge          = '0';
    public string $dUtilityCharge          = '0';

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
        $this->dUnits    = [$this->emptyUnitRow()];
    }

    /** A blank unit row for the sale invoice — each row carries its own property. */
    protected function emptyUnitRow(): array
    {
        return [
            'property_id'             => '',
            'property_unit_id'        => '',
            'sale_amount'             => '0',
            'discount_amount'         => '0',
            'tax_amount'              => '0',
            'service_charge'          => '0',
            'utility_charge'          => '0',
            'down_payment_percentage' => '0',
            'net_amount'              => '0',
        ];
    }

    /** Units available for a given property (cached per render via the view). */
    public function unitsForProperty(int|string|null $propertyId): \Illuminate\Support\Collection
    {
        if (!$propertyId) return collect();

        return PropertyUnit::where('property_id', $propertyId)
            ->orderBy('code')
            ->get();
    }

    // ── Unit rows (sale path) ──────────────────────────────────────────────────
    public function addUnitRow(): void
    {
        $this->dUnits[] = $this->emptyUnitRow();
    }

    public function removeUnitRow(int $index): void
    {
        if (count($this->dUnits) <= 1) {
            // Keep at least one row — just reset it.
            $this->dUnits = [$this->emptyUnitRow()];
        } else {
            unset($this->dUnits[$index]);
            $this->dUnits = array_values($this->dUnits);
        }
        $this->recalcSummaryFromUnits();
        $this->afterSummaryRecalc();
    }

    // ── Reactive hooks ────────────────────────────────────────────────────────
    public function updatedDPropertyId(): void
    {
        // Top-level property is the RENT path only — reset the single unit.
        $this->dPropertyUnitId        = '';
        $this->dDownPaymentPercentage = '0';
        $this->dDownPaymentAmount     = '0';
        $this->dServiceCharge         = '0';
        $this->dUtilityCharge         = '0';
        $this->recalcNet();
    }

    /**
     * Fires for any change inside the $dUnits array (Livewire dot-path updates).
     *  - property changed → reset that row's unit (it must belong to the property)
     *  - unit changed     → pre-fill that row's amounts from the PropertyUnit
     * Then recompute per-row nets and the combined summary.
     */
    public function updatedDUnits(mixed $value, ?string $key = null): void
    {
        if ($key !== null) {
            $index = (int) explode('.', $key)[0];

            if (str_ends_with($key, '.property_id')) {
                // New property for this row — clear its unit + amounts.
                $this->dUnits[$index]['property_unit_id']        = '';
                $this->dUnits[$index]['sale_amount']             = '0';
                $this->dUnits[$index]['discount_amount']         = '0';
                $this->dUnits[$index]['tax_amount']              = '0';
                $this->dUnits[$index]['service_charge']          = '0';
                $this->dUnits[$index]['utility_charge']          = '0';
                $this->dUnits[$index]['down_payment_percentage'] = '0';
            } elseif (str_ends_with($key, '.property_unit_id')) {
                $this->prefillUnitRow($index);
            }
        }

        $this->recalcSummaryFromUnits();
        $this->afterSummaryRecalc();
    }

    /** Pre-fill a row's amounts from the selected PropertyUnit. */
    protected function prefillUnitRow(int $index): void
    {
        $unitId = $this->dUnits[$index]['property_unit_id'] ?? '';
        if (!$unitId) return;

        // Disallow selecting the same unit twice in this invoice.
        foreach ($this->dUnits as $i => $row) {
            if ($i !== $index && (string) ($row['property_unit_id'] ?? '') === (string) $unitId) {
                $this->dUnits[$index]['property_unit_id'] = '';
                $this->dispatch('toast', ['type' => 'error', 'message' => 'This unit is already added to the invoice.']);
                return;
            }
        }

        $unit = PropertyUnit::find($unitId);
        if (!$unit) return;

        $price = (float) ($unit->price ?: $unit->sell_price ?: 0);
        $this->dUnits[$index]['sale_amount']             = (string) $price;
        $this->dUnits[$index]['service_charge']          = (string) (float) ($unit->service_charge ?? 0);
        $this->dUnits[$index]['utility_charge']          = (string) (float) ($unit->utility_charge ?? 0);
        $this->dUnits[$index]['down_payment_percentage'] = (string) (float) ($unit->down_payment_percentage ?? 0);
    }

    public function updatedDPropertyUnitId(): void
    {
        // RENT path only (single unit).
        if ($this->dSaleType !== 'rent' || !$this->dPropertyUnitId) return;

        $unit = PropertyUnit::find($this->dPropertyUnitId);
        if (!$unit) return;

        if ($unit->deposit_amount) {
            $this->dSecurityDepositAmount = (string) (float) $unit->deposit_amount;
        }
        $rentAmount = (float) ($unit->rent_amount ?: $unit->price ?: 0);
        if ($rentAmount > 0) $this->dScheduleAmount = (string) $rentAmount;
        $this->dServiceCharge = (string) (float) ($unit->service_charge ?? 0);
        if ($this->dIsScheduled) $this->generateSchedulePreview();
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
        // Switching type clears the cross-type state. Multi-unit rows only exist
        // for SALE; rent uses the single dPropertyUnitId. This structurally
        // prevents mixing sale + rent or multiple rents in one invoice.
        $this->schedulePreview        = [];
        $this->dPropertyUnitId        = '';
        $this->dUnits                 = [$this->emptyUnitRow()];
        $this->dSaleAmount            = '0';
        $this->dDiscountAmount        = '0';
        $this->dTaxAmount             = '0';
        $this->dDownPaymentPercentage = '0';
        $this->dDownPaymentAmount     = '0';
        $this->dSecurityDepositAmount = '0';
        $this->dScheduleAmount        = '0';
        $this->dServiceCharge         = '0';
        $this->dUtilityCharge         = '0';
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

    /**
     * SALE path: roll the per-unit rows up into the combined invoice summary.
     * Each row net = sale - discount + tax. Service charge is auto-summed into
     * the single combined dServiceCharge (the user can still override it after).
     */
    protected function recalcSummaryFromUnits(): void
    {
        $sale = $discount = $tax = $net = $service = $utility = 0.0;

        foreach ($this->dUnits as $i => $row) {
            $rs = (float) ($row['sale_amount'] ?? 0);
            $rd = (float) ($row['discount_amount'] ?? 0);
            $rt = (float) ($row['tax_amount'] ?? 0);
            $rsc = (float) ($row['service_charge'] ?? 0);
            $ruc = (float) ($row['utility_charge'] ?? 0);
            $rn = round($rs - $rd + $rt + $rsc + $ruc, 2);

            $this->dUnits[$i]['net_amount'] = (string) $rn;

            $sale     += $rs;
            $discount += $rd;
            $tax      += $rt;
            $net      += $rn;
            $service  += $rsc;
            $utility  += $ruc;
        }

        $this->dSaleAmount     = (string) round($sale, 2);
        $this->dDiscountAmount = (string) round($discount, 2);
        $this->dTaxAmount      = (string) round($tax, 2);
        $this->dNetAmount      = (string) round($net, 2);
        $this->dServiceCharge  = (string) round($service, 2);
        $this->dUtilityCharge  = (string) round($utility, 2);
    }

    /** Recompute downstream shared figures (down payment, installments) + preview. */
    protected function afterSummaryRecalc(): void
    {
        $this->recalcDownPaymentFromPercentage();
        $this->recalcScheduleAmount();
        if ($this->dIsScheduled) $this->generateSchedulePreview();
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
        $remaining = max(0.0, (float) $this->dNetAmount - (float) $this->dDownPaymentAmount - (float) $this->dServiceCharge - (float) $this->dUtilityCharge);
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
            'dSaleType'   => 'required|in:sale,rent',
            'dCustomerId' => 'required|exists:customers,id',
        ];

        $payload = [
            'dSaleType'   => $this->dSaleType,
            'dCustomerId' => $this->dCustomerId,
        ];

        if ($this->dSaleType === 'sale') {
            // Multi-unit / multi-property sale: each row picks its own property + unit.
            $rules['dUnits']                    = 'required|array|min:1';
            $rules['dUnits.*.property_id']      = 'required|exists:properties,id';
            $rules['dUnits.*.property_unit_id'] = 'required|exists:property_units,id';
            $rules['dUnits.*.sale_amount']      = 'required|numeric|min:0';
            $payload['dUnits'] = $this->dUnits;
        } else {
            // Rent: single property + single unit.
            $rules['dPropertyId']      = 'required|exists:properties,id';
            $rules['dPropertyUnitId']  = 'required|exists:property_units,id';
            $payload['dPropertyId']     = $this->dPropertyId;
            $payload['dPropertyUnitId'] = $this->dPropertyUnitId;
        }

        if ($this->dIsScheduled) {
            $rules['dScheduleCount']     = 'required|integer|min:1|max:360';
            $rules['dScheduleAmount']    = 'required|numeric|min:0';
            $rules['dScheduleStartDate'] = 'required|date';
            $rules['dScheduleDay']       = 'required|integer|min:1|max:28';
            $payload['dScheduleCount']     = $this->dScheduleCount;
            $payload['dScheduleAmount']    = $this->dScheduleAmount;
            $payload['dScheduleStartDate'] = $this->dScheduleStartDate;
            $payload['dScheduleDay']       = $this->dScheduleDay;
        }

        $validator = Validator::make($payload, $rules, [
            'dSaleType.required'                  => 'Please select a sale type.',
            'dPropertyId.required'                => 'Please select a property.',
            'dCustomerId.required'                => 'Please select a customer.',
            'dPropertyUnitId.required'            => 'Please select a property unit.',
            'dUnits.required'                     => 'Add at least one unit to the invoice.',
            'dUnits.min'                          => 'Add at least one unit to the invoice.',
            'dUnits.*.property_id.required'       => 'Please select a property for each row.',
            'dUnits.*.property_unit_id.required'  => 'Please select a unit for each row.',
            'dUnits.*.sale_amount.required'       => 'Sale amount is required for each unit.',
            'dScheduleCount.required'             => 'Schedule count is required.',
            'dScheduleAmount.required'            => 'Schedule amount is required.',
            'dScheduleStartDate.required'         => 'Schedule start date is required.',
        ]);

        // Reject duplicate units within the invoice (sale path).
        if ($this->dSaleType === 'sale') {
            $validator->after(function ($v): void {
                $ids = array_filter(array_map(fn ($r) => $r['property_unit_id'] ?? '', $this->dUnits));
                if (count($ids) !== count(array_unique($ids))) {
                    $v->errors()->add('dUnits', 'The same unit cannot be added more than once.');
                }
            });
        }

        if ($validator->fails()) {
            $this->setErrorBag($validator->errors());
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Please fix the validation errors.']);
            return;
        }

        // Recompute the combined summary from rows so persisted totals are authoritative.
        if ($this->dSaleType === 'sale') {
            $this->recalcSummaryFromUnits();
            $this->recalcDownPaymentFromPercentage();
            $this->recalcScheduleAmount();
        } else {
            $this->recalcNet();
        }

        // First row is the primary unit/property — keeps the single property_id /
        // property_unit_id columns populated for all existing readers.
        $primaryUnitId = $this->dSaleType === 'sale'
            ? (int) ($this->dUnits[0]['property_unit_id'] ?? 0)
            : (int) $this->dPropertyUnitId;

        $primaryPropertyId = $this->dSaleType === 'sale'
            ? (int) ($this->dUnits[0]['property_id'] ?? 0)
            : (int) $this->dPropertyId;

        $sale = DB::transaction(function () use ($primaryUnitId, $primaryPropertyId): PropertySale {
            $sale = PropertySale::create([
                'sale_type'               => $this->dSaleType,
                'property_id'             => $primaryPropertyId,
                'property_unit_id'        => $primaryUnitId,
                'customer_id'             => $this->dCustomerId,
                'sale_date'               => $this->dSaleDate ?: null,
                'contract_date'           => $this->dContractDate ?: null,
                // sale financials (combined)
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

            // Persist the per-unit breakdown rows.
            if ($this->dSaleType === 'sale') {
                foreach (array_values($this->dUnits) as $i => $row) {
                    $rs = (float) ($row['sale_amount'] ?? 0);
                    $rd = (float) ($row['discount_amount'] ?? 0);
                    $rt = (float) ($row['tax_amount'] ?? 0);
                    $rsc = (float) ($row['service_charge'] ?? 0);
                    $ruc = (float) ($row['utility_charge'] ?? 0);

                    PropertySaleUnit::create([
                        'property_sale_id'        => $sale->id,
                        'property_id'             => (int) ($row['property_id'] ?? 0),
                        'property_unit_id'        => (int) $row['property_unit_id'],
                        'sale_amount'             => $rs,
                        'discount_amount'         => $rd,
                        'tax_amount'              => $rt,
                        'net_amount'              => round($rs - $rd + $rt + $rsc + $ruc, 2),
                        'service_charge'          => $rsc,
                        'utility_charge'          => $ruc,
                        'down_payment_percentage' => ($row['down_payment_percentage'] ?? '') !== ''
                            ? (float) $row['down_payment_percentage'] : null,
                        'sort_order'              => $i,
                    ]);
                }
            } else {
                // Keep one row for consistency on the rent invoice too.
                PropertySaleUnit::create([
                    'property_sale_id' => $sale->id,
                    'property_id'      => $primaryPropertyId,
                    'property_unit_id' => $primaryUnitId,
                    'sale_amount'      => (float) $this->dSaleAmount,
                    'discount_amount'  => (float) $this->dDiscountAmount,
                    'tax_amount'       => (float) $this->dTaxAmount,
                    'net_amount'       => (float) $this->dNetAmount,
                    'service_charge'   => (float) $this->dServiceCharge,
                    'sort_order'       => 0,
                ]);
            }

            return $sale;
        });

        // Generate payment schedules — one down payment, one combined service
        // charge, one utility charge, one installment/rent series.
        app(ScheduleGeneratorService::class)->generateForSale(
            $sale,
            (float) $this->dServiceCharge,
            (float) $this->dUtilityCharge,
        );

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
