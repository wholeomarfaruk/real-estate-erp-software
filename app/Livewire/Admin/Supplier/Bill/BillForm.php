<?php

namespace App\Livewire\Admin\Supplier\Bill;

use App\Enums\Supplier\SupplierBillReferenceType;
use App\Enums\Supplier\SupplierBillStatus;
use App\Livewire\Admin\Supplier\Concerns\InteractsWithSupplierAccess;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\PurchaseOrder;
use App\Models\StockReceive;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Services\Supplier\SupplierLedgerService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class BillForm extends Component
{
    use InteractsWithSupplierAccess;

    public ?SupplierBill $billRecord = null;

    public ?int $billId = null;

    public bool $editMode = false;

    public string $bill_no = '';

    public ?int $supplier_id = null;

    public string $bill_date = '';

    public ?string $due_date = null;

    public string $reference_type = 'manual';

    public ?int $purchase_order_id = null;

    public ?int $stock_receive_id = null;

    public ?string $notes = null;

    public float|int|string $subtotal = 0;

    public float|int|string $discount_amount = 0;

    public float|int|string $tax_amount = 0;

    public float|int|string $other_charge = 0;

    public float|int|string $total_amount = 0;

    public float|int|string $paid_amount = 0;

    public float|int|string $due_amount = 0;

    public string $status = 'draft';

    /**
     * @var array<int, array{product_id:int|string|null,description:?string,qty:float|int|string,unit_id:int|string|null,rate:float|int|string,line_total:float|int|string}>
     */
    public array $items = [];

    public function mount(?SupplierBill $bill = null): void
    {
        if ($bill && $bill->exists) {
            $this->authorizePermission('supplier.bill.edit');

            if (! $bill->canEdit()) {
                abort(403, 'Only draft/open bills are editable.');
            }

            $this->editMode = true;
            $this->billRecord = $bill->load('items');
            $this->billId = $bill->id;

            $this->bill_no = $bill->bill_no;
            $this->supplier_id = $bill->supplier_id;
            $this->bill_date = optional($bill->bill_date)->format('Y-m-d') ?: now()->toDateString();
            $this->due_date = optional($bill->due_date)->format('Y-m-d');
            $this->reference_type = $bill->reference_type?->value ?? SupplierBillReferenceType::MANUAL->value;
            $this->purchase_order_id = $bill->purchase_order_id;
            $this->stock_receive_id = $bill->stock_receive_id;
            $this->notes = $bill->notes;
            $this->subtotal = (float) $bill->subtotal;
            $this->discount_amount = (float) $bill->discount_amount;
            $this->tax_amount = (float) $bill->tax_amount;
            $this->other_charge = (float) $bill->other_charge;
            $this->total_amount = (float) $bill->total_amount;
            $this->paid_amount = (float) $bill->paid_amount;
            $this->due_amount = (float) $bill->due_amount;
            $this->status = $bill->status?->value ?? SupplierBillStatus::DRAFT->value;

            $this->items = $bill->items
                ->map(fn ($item): array => [
                    'product_id' => $item->product_id,
                    'description' => $item->description,
                    'qty' => (float) $item->qty,
                    'unit_id' => $item->unit_id,
                    'rate' => (float) $item->rate,
                    'line_total' => (float) $item->line_total,
                ])
                ->values()
                ->all();

            if ($this->items === []) {
                $this->items[] = $this->blankItem();
            }

            $this->recalculateTotals();

            return;
        }

        $this->authorizePermission('supplier.bill.create');

        $this->bill_no = $this->generateBillNo();
        $this->bill_date = now()->toDateString();
        $this->due_date = now()->toDateString();
        $this->reference_type = SupplierBillReferenceType::MANUAL->value;
        $this->status = SupplierBillStatus::DRAFT->value;
        $this->items[] = $this->blankItem();
    }

    public function updatedReferenceType(string $value): void
    {
        if ($value === SupplierBillReferenceType::MANUAL->value) {
            $this->purchase_order_id = null;
            $this->stock_receive_id = null;

            if ($this->items === []) {
                $this->items[] = $this->blankItem();
            }

            $this->recalculateTotals();

            return;
        }

        if ($value === SupplierBillReferenceType::LINKED_PURCHASE_ORDER->value) {
            $this->stock_receive_id = null;

            if ($this->purchase_order_id) {
                $this->loadItemsFromPurchaseOrder($this->purchase_order_id);
            }

            return;
        }

        if ($value === SupplierBillReferenceType::LINKED_STOCK_RECEIVE->value) {
            $this->purchase_order_id = null;

            if ($this->stock_receive_id) {
                $this->loadItemsFromStockReceive($this->stock_receive_id);
            }
        }
    }

    public function updatedPurchaseOrderId($purchaseOrderId): void
    {
        if ($this->reference_type !== SupplierBillReferenceType::LINKED_PURCHASE_ORDER->value) {
            return;
        }

        if (! $purchaseOrderId) {
            $this->items = [];
            $this->recalculateTotals();

            return;
        }

        $this->loadItemsFromPurchaseOrder((int) $purchaseOrderId);
    }

    public function updatedStockReceiveId($stockReceiveId): void
    {
        if ($this->reference_type !== SupplierBillReferenceType::LINKED_STOCK_RECEIVE->value) {
            return;
        }

        if (! $stockReceiveId) {
            $this->items = [];
            $this->recalculateTotals();

            return;
        }

        $this->loadItemsFromStockReceive((int) $stockReceiveId);
    }

    public function updatedPaidAmount(): void
    {
        $this->recalculateTotals();
    }

    public function updatedDiscountAmount(): void
    {
        $this->recalculateTotals();
    }

    public function updatedTaxAmount(): void
    {
        $this->recalculateTotals();
    }

    public function updatedOtherCharge(): void
    {
        $this->recalculateTotals();
    }

    public function updatedItems($value, string $name): void
    {
        if (! str_contains($name, '.')) {
            return;
        }

        [$index] = explode('.', $name);
        $this->recalculateItem((int) $index);
        $this->recalculateTotals();
    }

    public function addItem(): void
    {
        if (! $this->isManualMode()) {
            return;
        }

        $this->items[] = $this->blankItem();
    }

    public function removeItem(int $index): void
    {
        if (! $this->isManualMode() || count($this->items) <= 1) {
            return;
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->recalculateTotals();
    }

    public function save()
    {
        $wasEditMode = $this->editMode;

        if ($this->editMode) {
            $this->authorizePermission('supplier.bill.edit');
        } else {
            $this->authorizePermission('supplier.bill.create');
        }

        if ($this->editMode && $this->billRecord && ! $this->billRecord->canEdit()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only draft/open bills are editable.']);

            return redirect()->route('admin.supplier.bills.index');
        }

        $this->normalizeItems();
        $this->recalculateTotals();

        $validated = $this->validate($this->rules(), $this->messages());

        if (! $this->ensureReferenceConsistency($validated)) {
            return;
        }

        $this->normalizeItems();
        $this->recalculateTotals();

        $preparedItems = $this->prepareItemsForSave();

        if ($preparedItems === []) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'At least one bill item is required.']);

            return;
        }

        $preserveDraft = $validated['status'] === SupplierBillStatus::DRAFT->value;

        $payload = [
            'supplier_id' => $validated['supplier_id'],
            'bill_no' => $validated['bill_no'],
            'bill_date' => $validated['bill_date'],
            'due_date' => $validated['due_date'],
            'reference_type' => $validated['reference_type'],
            'reference_id' => $this->resolveReferenceId($validated),
            'purchase_order_id' => $validated['purchase_order_id'],
            'stock_receive_id' => $validated['stock_receive_id'],
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'other_charge' => $this->other_charge,
            'total_amount' => $this->total_amount,
            'paid_amount' => $validated['paid_amount'],
            'due_amount' => $this->due_amount,
            'status' => $validated['status'],
            'notes' => $validated['notes'],
            'updated_by' => auth()->id(),
        ];

        DB::transaction(function () use ($payload, $preparedItems, $preserveDraft): void {
            if ($this->editMode && $this->billRecord) {
                $bill = $this->billRecord->fresh();

                if (! $bill || ! $bill->canEdit()) {
                    throw new \DomainException('Only draft/open bills are editable.');
                }

                $bill->fill($payload);
                $bill->syncAmountsAndStatus($preserveDraft);
                $bill->save();
                $bill->items()->delete();
            } else {
                $bill = new SupplierBill($payload);
                $bill->created_by = auth()->id();
                $bill->syncAmountsAndStatus($preserveDraft);
                $bill->save();

                $this->billRecord = $bill;
                $this->billId = $bill->id;
                $this->editMode = true;
            }

            foreach ($preparedItems as $index => $item) {
                $bill->items()->create([
                    'product_id' => $item['product_id'],
                    'description' => $item['description'],
                    'qty' => $item['qty'],
                    'unit_id' => $item['unit_id'],
                    'rate' => $item['rate'],
                    'line_total' => $item['line_total'],
                    'sort_order' => $index + 1,
                ]);
            }

            app(SupplierLedgerService::class)->postBill($bill, (int) auth()->id(), false);
        });

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => $wasEditMode ? 'Supplier bill updated successfully.' : 'Supplier bill created successfully.',
        ]);

        return redirect()->route('admin.supplier.bills.index');
    }

    public function render(): View
    {
        if ($this->editMode) {
            $this->authorizePermission('supplier.bill.edit');
        } else {
            $this->authorizePermission('supplier.bill.create');
        }

        $purchaseOrdersQuery = PurchaseOrder::query()
            ->with('supplier:id,name')
            ->latest('order_date')
            ->latest('id')
            ->when($this->supplier_id, fn ($query) => $query->where('supplier_id', $this->supplier_id))
            ->limit(200);

        $stockReceivesQuery = StockReceive::query()
            ->with(['supplier:id,name', 'purchaseOrder:id,po_no'])
            ->latest('receive_date')
            ->latest('id')
            ->when($this->supplier_id, fn ($query) => $query->where('supplier_id', $this->supplier_id))
            ->limit(200);

        return view('livewire.admin.supplier.bill.bill-form', [
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name', 'code']),
            'purchaseOrders' => $purchaseOrdersQuery->get(['id', 'po_no', 'supplier_id', 'status']),
            'stockReceives' => $stockReceivesQuery->get(['id', 'receive_no', 'supplier_id', 'purchase_order_id', 'receive_date']),
            'products' => Product::query()->active()->orderBy('name')->get(['id', 'name', 'sku', 'product_unit_id']),
            'units' => ProductUnit::query()->active()->orderBy('name')->get(['id', 'name']),
            'referenceTypes' => SupplierBillReferenceType::cases(),
            'statuses' => [
                SupplierBillStatus::DRAFT,
                SupplierBillStatus::OPEN,
                SupplierBillStatus::PARTIAL,
                SupplierBillStatus::PAID,
                SupplierBillStatus::OVERDUE,
            ],
            'manualMode' => $this->isManualMode(),
        ])->layout('layouts.admin.admin');
    }

    protected function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'bill_no' => ['required', 'string', 'max:100', Rule::unique('supplier_bills', 'bill_no')->ignore($this->billId)],
            'bill_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:bill_date'],
            'reference_type' => ['required', Rule::enum(SupplierBillReferenceType::class)],
            'purchase_order_id' => [
                Rule::requiredIf(fn (): bool => $this->reference_type === SupplierBillReferenceType::LINKED_PURCHASE_ORDER->value),
                'nullable',
                'integer',
                'exists:purchase_orders,id',
            ],
            'stock_receive_id' => [
                Rule::requiredIf(fn (): bool => $this->reference_type === SupplierBillReferenceType::LINKED_STOCK_RECEIVE->value),
                'nullable',
                'integer',
                'exists:stock_receives,id',
            ],
            'notes' => ['nullable', 'string'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['required', 'numeric', 'min:0'],
            'other_charge' => ['required', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'due_amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in([
                SupplierBillStatus::DRAFT->value,
                SupplierBillStatus::OPEN->value,
                SupplierBillStatus::PARTIAL->value,
                SupplierBillStatus::PAID->value,
                SupplierBillStatus::OVERDUE->value,
            ])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.description' => ['nullable', 'string', 'max:1000'],
            'items.*.qty' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_id' => ['nullable', 'integer', 'exists:product_units,id'],
            'items.*.rate' => ['required', 'numeric', 'min:0'],
            'items.*.line_total' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function messages(): array
    {
        return [
            'supplier_id.required' => 'Please select a supplier.',
            'bill_no.required' => 'Bill number is required.',
            'bill_no.unique' => 'This bill number is already used.',
            'purchase_order_id.required' => 'Please select a purchase order for linked PO mode.',
            'stock_receive_id.required' => 'Please select a stock receive for linked mode.',
            'items.min' => 'At least one item is required.',
            'items.*.qty.min' => 'Item quantity must be greater than zero.',
        ];
    }

    /**
     * @return array{product_id:null,description:null,qty:float,unit_id:null,rate:float,line_total:float}
     */
    protected function blankItem(): array
    {
        return [
            'product_id' => null,
            'description' => null,
            'qty' => 1,
            'unit_id' => null,
            'rate' => 0,
            'line_total' => 0,
        ];
    }

    protected function isManualMode(): bool
    {
        return $this->reference_type === SupplierBillReferenceType::MANUAL->value;
    }

    protected function recalculateItem(int $index): void
    {
        if (! isset($this->items[$index])) {
            return;
        }

        $qty = max(0, round((float) ($this->items[$index]['qty'] ?? 0), 3));
        $rate = max(0, round((float) ($this->items[$index]['rate'] ?? 0), 2));

        $this->items[$index]['qty'] = $qty;
        $this->items[$index]['rate'] = $rate;
        $this->items[$index]['line_total'] = round($qty * $rate, 2);
    }

    protected function normalizeItems(): void
    {
        foreach (array_keys($this->items) as $index) {
            $this->recalculateItem($index);
        }
    }

    protected function recalculateTotals(): void
    {
        $this->subtotal = round(collect($this->items)->sum(fn (array $item): float => (float) ($item['line_total'] ?? 0)), 2);

        $discount = max(0, round((float) $this->discount_amount, 2));
        $tax = max(0, round((float) $this->tax_amount, 2));
        $other = max(0, round((float) $this->other_charge, 2));
        $paid = max(0, round((float) $this->paid_amount, 2));

        $this->discount_amount = $discount;
        $this->tax_amount = $tax;
        $this->other_charge = $other;
        $this->paid_amount = $paid;

        $this->total_amount = round(max(0, (float) $this->subtotal - $discount + $tax + $other), 2);
        $this->due_amount = round(max(0, (float) $this->total_amount - $paid), 2);

        if ($this->status !== SupplierBillStatus::DRAFT->value) {
            $statusPreview = new SupplierBill([
                'due_date' => $this->due_date,
                'due_amount' => $this->due_amount,
                'paid_amount' => $this->paid_amount,
                'status' => SupplierBillStatus::OPEN->value,
            ]);

            $this->status = $statusPreview->resolveStatus()->value;
        }
    }

    protected function ensureReferenceConsistency(array &$validated): bool
    {
        if ($validated['reference_type'] === SupplierBillReferenceType::MANUAL->value) {
            $validated['purchase_order_id'] = null;
            $validated['stock_receive_id'] = null;

            return true;
        }

        if ($validated['reference_type'] === SupplierBillReferenceType::LINKED_PURCHASE_ORDER->value) {
            $purchaseOrder = PurchaseOrder::query()
                ->with(['items.product:id,name,product_unit_id', 'supplier:id,name'])
                ->find($validated['purchase_order_id']);

            if (! $purchaseOrder) {
                $this->dispatch('toast', ['type' => 'error', 'message' => 'Selected purchase order is invalid.']);

                return false;
            }

            if ($purchaseOrder->supplier_id && (int) $validated['supplier_id'] !== (int) $purchaseOrder->supplier_id) {
                $this->dispatch('toast', ['type' => 'error', 'message' => 'Selected supplier does not match purchase order supplier.']);

                return false;
            }

            $this->loadItemsFromPurchaseOrder((int) $purchaseOrder->id);
            $validated['stock_receive_id'] = null;

            return true;
        }

        if ($validated['reference_type'] === SupplierBillReferenceType::LINKED_STOCK_RECEIVE->value) {
            $stockReceive = StockReceive::query()
                ->with(['items.product:id,name,product_unit_id', 'supplier:id,name'])
                ->find($validated['stock_receive_id']);

            if (! $stockReceive) {
                $this->dispatch('toast', ['type' => 'error', 'message' => 'Selected stock receive is invalid.']);

                return false;
            }

            if ($stockReceive->supplier_id && (int) $validated['supplier_id'] !== (int) $stockReceive->supplier_id) {
                $this->dispatch('toast', ['type' => 'error', 'message' => 'Selected supplier does not match stock receive supplier.']);

                return false;
            }

            $this->loadItemsFromStockReceive((int) $stockReceive->id);
            $validated['purchase_order_id'] = $stockReceive->purchase_order_id;

            return true;
        }

        return true;
    }

    /**
     * @return array<int, array{product_id:int|null,description:?string,qty:float,unit_id:int|null,rate:float,line_total:float}>
     */
    protected function prepareItemsForSave(): array
    {
        $prepared = [];

        foreach ($this->items as $item) {
            $qty = round(max(0, (float) ($item['qty'] ?? 0)), 3);
            $rate = round(max(0, (float) ($item['rate'] ?? 0)), 2);
            $lineTotal = round($qty * $rate, 2);
            $description = isset($item['description']) ? trim((string) $item['description']) : null;
            $productId = isset($item['product_id']) && $item['product_id'] !== '' ? (int) $item['product_id'] : null;
            $unitId = isset($item['unit_id']) && $item['unit_id'] !== '' ? (int) $item['unit_id'] : null;

            if ($qty <= 0) {
                continue;
            }

            if ($this->isManualMode() && ! $productId && ! $description) {
                continue;
            }

            $prepared[] = [
                'product_id' => $productId,
                'description' => $description ?: null,
                'qty' => $qty,
                'unit_id' => $unitId,
                'rate' => $rate,
                'line_total' => $lineTotal,
            ];
        }

        return $prepared;
    }

    protected function resolveReferenceId(array $validated): ?int
    {
        return match ($validated['reference_type']) {
            SupplierBillReferenceType::LINKED_PURCHASE_ORDER->value => $validated['purchase_order_id'] ? (int) $validated['purchase_order_id'] : null,
            SupplierBillReferenceType::LINKED_STOCK_RECEIVE->value => $validated['stock_receive_id'] ? (int) $validated['stock_receive_id'] : null,
            default => null,
        };
    }

    protected function loadItemsFromPurchaseOrder(int $purchaseOrderId): void
    {
        $purchaseOrder = PurchaseOrder::query()
            ->with(['items.product:id,name,sku,product_unit_id', 'supplier:id,name'])
            ->find($purchaseOrderId);

        if (! $purchaseOrder) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Purchase order not found.']);

            return;
        }

        if ($purchaseOrder->supplier_id) {
            $this->supplier_id = (int) $purchaseOrder->supplier_id;
        }

        $rows = [];

        foreach ($purchaseOrder->items as $item) {
            $qty = round((float) ($item->approved_quantity ?: $item->quantity), 3);
            $rate = round((float) ($item->approved_unit_price ?: $item->estimated_unit_price), 2);
            $productName = $item->product?->name ?? 'PO Item';

            $rows[] = [
                'product_id' => $item->product_id,
                'description' => $productName,
                'qty' => $qty,
                'unit_id' => $item->product?->product_unit_id,
                'rate' => $rate,
                'line_total' => round($qty * $rate, 2),
            ];
        }

        if ($rows === []) {
            $rows[] = $this->blankItem();
        }

        $this->items = $rows;
        $this->recalculateTotals();
    }

    protected function loadItemsFromStockReceive(int $stockReceiveId): void
    {
        $stockReceive = StockReceive::query()
            ->with(['items.product:id,name,sku,product_unit_id', 'supplier:id,name'])
            ->find($stockReceiveId);

        if (! $stockReceive) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Stock receive not found.']);

            return;
        }

        if ($stockReceive->supplier_id) {
            $this->supplier_id = (int) $stockReceive->supplier_id;
        }

        if ($stockReceive->purchase_order_id) {
            $this->purchase_order_id = (int) $stockReceive->purchase_order_id;
        }

        $rows = [];

        foreach ($stockReceive->items as $item) {
            $qty = round((float) $item->quantity, 3);
            $rate = round((float) $item->unit_price, 2);
            $productName = $item->product?->name ?? 'Stock Receive Item';

            $rows[] = [
                'product_id' => $item->product_id,
                'description' => $productName,
                'qty' => $qty,
                'unit_id' => $item->product?->product_unit_id,
                'rate' => $rate,
                'line_total' => round($qty * $rate, 2),
            ];
        }

        if ($rows === []) {
            $rows[] = $this->blankItem();
        }

        $this->items = $rows;
        $this->recalculateTotals();
    }

    protected function generateBillNo(): string
    {
        $counter = max(1, SupplierBill::query()->withTrashed()->count() + 1);

        do {
            $billNo = 'SBL-'.str_pad((string) $counter, 6, '0', STR_PAD_LEFT);
            $exists = SupplierBill::query()->withTrashed()->where('bill_no', $billNo)->exists();
            $counter++;
        } while ($exists);

        return $billNo;
    }
}
