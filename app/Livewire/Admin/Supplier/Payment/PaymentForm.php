<?php

namespace App\Livewire\Admin\Supplier\Payment;

use App\Enums\Supplier\SupplierBillStatus;
use App\Enums\Supplier\SupplierPaymentMethod;
use App\Enums\Supplier\SupplierPaymentStatus;
use App\Livewire\Admin\Supplier\Concerns\InteractsWithSupplierAccess;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Models\SupplierPayment;
use App\Services\Supplier\SupplierPaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PaymentForm extends Component
{
    use InteractsWithSupplierAccess;

    public ?SupplierPayment $paymentRecord = null;

    public ?int $paymentId = null;

    public bool $editMode = false;

    public ?int $supplier_id = null;

    public string $payment_no = '';

    public string $payment_date = '';

    public string $payment_method = 'cash';

    public ?string $account_name = null;

    public ?string $account_reference = null;

    public ?string $reference_no = null;

    public ?string $transaction_no = null;

    public ?string $cheque_no = null;

    public ?string $remarks = null;

    public float|int|string $total_amount = 0;

    public float|int|string $allocated_amount = 0;

    public float|int|string $unallocated_amount = 0;

    public string $status = 'draft';

    /**
     * @var array<int, array{
     *   supplier_bill_id:int,
     *   bill_no:string,
     *   bill_date:?string,
     *   due_date:?string,
     *   total_amount:float,
     *   paid_amount:float,
     *   due_amount:float,
     *   allocate_now:float|int|string,
     *   notes:?string
     * }>
     */
    public array $allocations = [];

    public function mount(?SupplierPayment $payment = null): void
    {
        if ($payment && $payment->exists) {
            $this->authorizePermission('supplier.payment.edit');

            if (! $payment->canEdit()) {
                abort(403, 'Only draft payments are editable.');
            }

            $this->editMode = true;
            $this->paymentRecord = $payment->load('allocations');
            $this->paymentId = $payment->id;
            $this->supplier_id = $payment->supplier_id;
            $this->payment_no = $payment->payment_no;
            $this->payment_date = optional($payment->payment_date)->format('Y-m-d') ?: now()->toDateString();
            $this->payment_method = $payment->payment_method?->value ?? SupplierPaymentMethod::CASH->value;
            $this->account_name = $payment->account_name;
            $this->account_reference = $payment->account_reference;
            $this->reference_no = $payment->reference_no;
            $this->transaction_no = $payment->transaction_no;
            $this->cheque_no = $payment->cheque_no;
            $this->remarks = $payment->remarks;
            $this->total_amount = (float) $payment->total_amount;
            $this->allocated_amount = (float) $payment->allocated_amount;
            $this->unallocated_amount = (float) $payment->unallocated_amount;
            $this->status = $payment->status?->value ?? SupplierPaymentStatus::DRAFT->value;

            $this->loadSupplierBillsForAllocation();

            return;
        }

        $this->authorizePermission('supplier.payment.create');

        $this->payment_no = app(SupplierPaymentService::class)->generatePaymentNo();
        $this->payment_date = now()->toDateString();
        $this->payment_method = SupplierPaymentMethod::CASH->value;
        $this->status = SupplierPaymentStatus::DRAFT->value;
        $this->total_amount = 0;
        $this->allocated_amount = 0;
        $this->unallocated_amount = 0;
    }

    public function updatedSupplierId(): void
    {
        $this->loadSupplierBillsForAllocation();
    }

    public function updatedTotalAmount(): void
    {
        $this->recalculateAmounts();
    }

    public function updatedAllocations($value, string $name): void
    {
        if (! str_contains($name, '.')) {
            return;
        }

        [$index, $field] = explode('.', $name, 2);

        if ($field === 'allocate_now' && isset($this->allocations[(int) $index])) {
            $this->allocations[(int) $index]['allocate_now'] = max(0, round((float) $this->allocations[(int) $index]['allocate_now'], 2));
        }

        $this->recalculateAmounts();
    }

    public function save()
    {
        $wasEditMode = $this->editMode;

        if ($this->editMode) {
            $this->authorizePermission('supplier.payment.edit');
        } else {
            $this->authorizePermission('supplier.payment.create');
        }

        if ($this->editMode && $this->paymentRecord && ! $this->paymentRecord->canEdit()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only draft payments are editable.']);

            return redirect()->route('admin.supplier.payments.index');
        }

        $this->recalculateAmounts();

        $validated = $this->validate($this->rules(), $this->messages());
        $preparedAllocations = $this->prepareAllocations();

        if ($preparedAllocations !== [] && ! $this->canAllocateBills()) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'You do not have permission to allocate supplier payments.',
            ]);

            return null;
        }

        try {
            $this->validatePreparedAllocations($preparedAllocations, (int) $validated['supplier_id'], (float) $validated['total_amount']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);

            return null;
        }

        $status = $validated['status'] === SupplierPaymentStatus::DRAFT->value
            ? SupplierPaymentStatus::DRAFT->value
            : SupplierPaymentStatus::POSTED->value;

        $payload = [
            'supplier_id' => $validated['supplier_id'],
            'payment_no' => $validated['payment_no'],
            'payment_date' => $validated['payment_date'],
            'payment_method' => $validated['payment_method'],
            'account_name' => $validated['account_name'],
            'account_reference' => $validated['account_reference'],
            'reference_no' => $validated['reference_no'],
            'transaction_no' => $validated['transaction_no'],
            'cheque_no' => $validated['cheque_no'],
            'remarks' => $validated['remarks'],
            'total_amount' => round((float) $validated['total_amount'], 2),
            'allocated_amount' => round((float) $this->allocated_amount, 2),
            'unallocated_amount' => round((float) $this->unallocated_amount, 2),
            'status' => $status,
        ];

        try {
            $savedPayment = app(SupplierPaymentService::class)->savePayment(
                payload: $payload,
                allocations: $preparedAllocations,
                payment: $this->paymentRecord,
                actorId: (int) auth()->id()
            );
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);

            return null;
        }

        $this->paymentRecord = $savedPayment;
        $this->paymentId = $savedPayment->id;
        $this->editMode = true;

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => $wasEditMode ? 'Supplier payment updated successfully.' : 'Supplier payment created successfully.',
        ]);

        return redirect()->route('admin.supplier.payments.index');
    }

    public function render(): View
    {
        if ($this->editMode) {
            $this->authorizePermission('supplier.payment.edit');
        } else {
            $this->authorizePermission('supplier.payment.create');
        }

        return view('livewire.admin.supplier.payment.payment-form', [
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name', 'code']),
            'paymentMethods' => SupplierPaymentMethod::cases(),
            'statusOptions' => [
                SupplierPaymentStatus::DRAFT,
                SupplierPaymentStatus::POSTED,
            ],
            'canAllocateBills' => $this->canAllocateBills(),
        ])->layout('layouts.admin.admin');
    }

    protected function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'payment_no' => ['required', 'string', 'max:100', Rule::unique('supplier_payments', 'payment_no')->ignore($this->paymentId)],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::enum(SupplierPaymentMethod::class)],
            'account_name' => ['nullable', 'string', 'max:255'],
            'account_reference' => ['nullable', 'string', 'max:255'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'transaction_no' => ['nullable', 'string', 'max:255'],
            'cheque_no' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
            'total_amount' => ['required', 'numeric', 'gt:0'],
            'allocated_amount' => ['required', 'numeric', 'min:0'],
            'unallocated_amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in([
                SupplierPaymentStatus::DRAFT->value,
                SupplierPaymentStatus::POSTED->value,
            ])],
            'allocations' => ['nullable', 'array'],
            'allocations.*.supplier_bill_id' => ['required', 'integer', 'exists:supplier_bills,id'],
            'allocations.*.allocate_now' => ['nullable', 'numeric', 'min:0'],
            'allocations.*.notes' => ['nullable', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'supplier_id.required' => 'Please select a supplier.',
            'payment_no.required' => 'Payment number is required.',
            'payment_no.unique' => 'This payment number already exists.',
            'total_amount.gt' => 'Total amount must be greater than zero.',
        ];
    }

    protected function recalculateAmounts(): void
    {
        $this->total_amount = round(max(0, (float) $this->total_amount), 2);

        $allocated = collect($this->allocations)->sum(function (array $row): float {
            return round(max(0, (float) ($row['allocate_now'] ?? 0)), 2);
        });

        $this->allocated_amount = round($allocated, 2);
        $this->unallocated_amount = round(max(0, (float) $this->total_amount - (float) $this->allocated_amount), 2);
    }

    protected function loadSupplierBillsForAllocation(): void
    {
        SupplierBill::syncOverdueStatuses();

        if (! $this->supplier_id) {
            $this->allocations = [];
            $this->recalculateAmounts();

            return;
        }

        $existingAllocations = collect();

        if ($this->editMode && $this->paymentRecord) {
            $existingAllocations = $this->paymentRecord->allocations
                ->keyBy(fn ($allocation) => (int) $allocation->supplier_bill_id);
        }

        $pendingBills = SupplierBill::query()
            ->where('supplier_id', $this->supplier_id)
            ->whereIn('status', [
                SupplierBillStatus::OPEN->value,
                SupplierBillStatus::PARTIAL->value,
                SupplierBillStatus::OVERDUE->value,
            ])
            ->where('due_amount', '>', 0)
            ->orderBy('due_date')
            ->orderBy('id')
            ->get([
                'id',
                'bill_no',
                'bill_date',
                'due_date',
                'total_amount',
                'paid_amount',
                'due_amount',
            ]);

        $rows = $pendingBills->map(function (SupplierBill $bill) use ($existingAllocations): array {
            $existing = $existingAllocations->get((int) $bill->id);

            return [
                'supplier_bill_id' => (int) $bill->id,
                'bill_no' => $bill->bill_no,
                'bill_date' => optional($bill->bill_date)->format('Y-m-d'),
                'due_date' => optional($bill->due_date)->format('Y-m-d'),
                'total_amount' => (float) $bill->total_amount,
                'paid_amount' => (float) $bill->paid_amount,
                'due_amount' => (float) $bill->due_amount,
                'allocate_now' => (float) ($existing?->allocated_amount ?? 0),
                'notes' => $existing?->notes,
            ];
        })->values()->all();

        $existingBillIds = collect($rows)->pluck('supplier_bill_id')->all();

        if ($existingAllocations->isNotEmpty()) {
            $missingBillIds = $existingAllocations->keys()
                ->map(fn ($id): int => (int) $id)
                ->filter(fn ($id): bool => ! in_array($id, $existingBillIds, true))
                ->values()
                ->all();

            if ($missingBillIds !== []) {
                $missingBills = SupplierBill::query()
                    ->whereIn('id', $missingBillIds)
                    ->get([
                        'id',
                        'bill_no',
                        'bill_date',
                        'due_date',
                        'total_amount',
                        'paid_amount',
                        'due_amount',
                    ]);

                foreach ($missingBills as $bill) {
                    $existing = $existingAllocations->get((int) $bill->id);

                    $rows[] = [
                        'supplier_bill_id' => (int) $bill->id,
                        'bill_no' => $bill->bill_no,
                        'bill_date' => optional($bill->bill_date)->format('Y-m-d'),
                        'due_date' => optional($bill->due_date)->format('Y-m-d'),
                        'total_amount' => (float) $bill->total_amount,
                        'paid_amount' => (float) $bill->paid_amount,
                        'due_amount' => (float) $bill->due_amount,
                        'allocate_now' => (float) ($existing?->allocated_amount ?? 0),
                        'notes' => $existing?->notes,
                    ];
                }
            }
        }

        $this->allocations = $rows;
        $this->recalculateAmounts();
    }

    /**
     * @return array<int, array{supplier_bill_id:int,allocated_amount:float,notes:?string}>
     */
    protected function prepareAllocations(): array
    {
        $prepared = [];

        foreach ($this->allocations as $row) {
            $billId = (int) ($row['supplier_bill_id'] ?? 0);
            $allocatedAmount = round(max(0, (float) ($row['allocate_now'] ?? 0)), 2);
            $notes = isset($row['notes']) ? trim((string) $row['notes']) : null;

            if ($billId <= 0 || $allocatedAmount <= 0) {
                continue;
            }

            $prepared[] = [
                'supplier_bill_id' => $billId,
                'allocated_amount' => $allocatedAmount,
                'notes' => $notes ?: null,
            ];
        }

        return $prepared;
    }

    /**
     * @param  array<int, array{supplier_bill_id:int,allocated_amount:float,notes:?string}>  $allocations
     */
    protected function validatePreparedAllocations(array $allocations, int $supplierId, float $totalAmount): void
    {
        $totalAllocated = round(collect($allocations)->sum('allocated_amount'), 2);

        if ($totalAllocated - $totalAmount > 0.0001) {
            throw new \DomainException('Total allocation cannot exceed payment total amount.');
        }

        if ($allocations === []) {
            return;
        }

        $billIds = collect($allocations)->pluck('supplier_bill_id')->unique()->values()->all();
        $bills = SupplierBill::query()
            ->whereIn('id', $billIds)
            ->get()
            ->keyBy('id');

        foreach ($allocations as $allocation) {
            $bill = $bills->get($allocation['supplier_bill_id']);

            if (! $bill) {
                throw new \DomainException('One or more allocated bills are invalid.');
            }

            if ((int) $bill->supplier_id !== $supplierId) {
                throw new \DomainException('All allocated bills must belong to selected supplier.');
            }

            if (! in_array($bill->status, [
                SupplierBillStatus::OPEN,
                SupplierBillStatus::PARTIAL,
                SupplierBillStatus::OVERDUE,
            ], true) || (float) $bill->due_amount <= 0) {
                throw new \DomainException('Only open, partial, or overdue bills can be allocated.');
            }

            if ((float) $allocation['allocated_amount'] - (float) $bill->due_amount > 0.0001) {
                throw new \DomainException('Allocated amount cannot exceed bill due amount.');
            }
        }
    }

    protected function canAllocateBills(): bool
    {
        return (bool) auth()->user()?->can('supplier.payment.allocate');
    }
}
