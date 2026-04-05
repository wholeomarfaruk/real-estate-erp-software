<?php

namespace App\Livewire\Admin\Supplier\Return;

use App\Enums\Supplier\SupplierBillStatus;
use App\Enums\Supplier\SupplierReturnReferenceType;
use App\Enums\Supplier\SupplierReturnStatus;
use App\Livewire\Admin\Supplier\Concerns\InteractsWithSupplierAccess;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\PurchaseOrder;
use App\Models\StockReceive;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Models\SupplierReturn;
use App\Services\Supplier\SupplierReturnService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SupplierReturnForm extends Component
{
    use InteractsWithSupplierAccess;

    public ?SupplierReturn $returnRecord = null;

    public ?int $returnId = null;

    public bool $editMode = false;

    public ?int $supplier_id = null;

    public string $return_no = '';

    public string $return_date = '';

    public string $reference_type = 'manual';

    public ?int $supplier_bill_id = null;

    public ?int $stock_receive_id = null;

    public ?int $purchase_order_id = null;

    public ?string $reason = null;

    public ?string $notes = null;

    public float|int|string $subtotal = 0;

    public float|int|string $total_amount = 0;

    public string $status = 'draft';

    /**
     * @var array<int, array{product_id:int|string|null,description:?string,qty:float|int|string,unit_id:int|string|null,rate:float|int|string,line_total:float|int|string}>
     */
    public array $items = [];

    public function mount(?SupplierReturn $return = null): void
    {
        if ($return && $return->exists) {
            $this->authorizePermission('supplier.return.edit');

            if (! $return->canEdit()) {
                abort(403, 'Only draft supplier returns are editable.');
            }

            $this->editMode = true;
            $this->returnRecord = $return->load('items');
            $this->returnId = $return->id;

            $this->supplier_id = $return->supplier_id;
            $this->return_no = $return->return_no;
            $this->return_date = optional($return->return_date)->format('Y-m-d') ?: now()->toDateString();
            $this->reference_type = $return->reference_type?->value ?? SupplierReturnReferenceType::MANUAL->value;
            $this->supplier_bill_id = $return->supplier_bill_id;
            $this->stock_receive_id = $return->stock_receive_id;
            $this->purchase_order_id = $return->purchase_order_id;
            $this->reason = $return->reason;
            $this->notes = $return->notes;
            $this->subtotal = (float) $return->subtotal;
            $this->total_amount = (float) $return->total_amount;
            $this->status = $return->status?->value ?? SupplierReturnStatus::DRAFT->value;

            $this->items = $return->items
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

        $this->authorizePermission('supplier.return.create');

        $this->return_no = app(SupplierReturnService::class)->generateReturnNo();
        $this->return_date = now()->toDateString();
        $this->reference_type = SupplierReturnReferenceType::MANUAL->value;
        $this->status = SupplierReturnStatus::DRAFT->value;
        $this->items[] = $this->blankItem();
    }

    public function updatedReferenceType(string $value): void
    {
        if ($value === SupplierReturnReferenceType::MANUAL->value) {
            $this->supplier_bill_id = null;
            $this->stock_receive_id = null;
            $this->purchase_order_id = null;

            if ($this->items === []) {
                $this->items[] = $this->blankItem();
            }

            $this->recalculateTotals();

            return;
        }

        if ($value === SupplierReturnReferenceType::SUPPLIER_BILL->value) {
            $this->stock_receive_id = null;

            if ($this->supplier_bill_id) {
                $this->loadItemsFromSupplierBill($this->supplier_bill_id);
            }

            return;
        }

        if ($value === SupplierReturnReferenceType::STOCK_RECEIVE->value) {
            $this->supplier_bill_id = null;

            if ($this->stock_receive_id) {
                $this->loadItemsFromStockReceive($this->stock_receive_id);
            }

            return;
        }

        if ($value === SupplierReturnReferenceType::PURCHASE_ORDER->value) {
            $this->supplier_bill_id = null;
            $this->stock_receive_id = null;

            if ($this->purchase_order_id) {
                $this->loadItemsFromPurchaseOrder($this->purchase_order_id);
            }
        }
    }

    public function updatedSupplierBillId($supplierBillId): void
    {
        if ($this->reference_type !== SupplierReturnReferenceType::SUPPLIER_BILL->value) {
            return;
        }

        if (! $supplierBillId) {
            $this->items = [];
            $this->recalculateTotals();

            return;
        }

        $this->loadItemsFromSupplierBill((int) $supplierBillId);
    }

    public function updatedStockReceiveId($stockReceiveId): void
    {
        if ($this->reference_type !== SupplierReturnReferenceType::STOCK_RECEIVE->value) {
            return;
        }

        if (! $stockReceiveId) {
            $this->items = [];
            $this->recalculateTotals();

            return;
        }

        $this->loadItemsFromStockReceive((int) $stockReceiveId);
    }

    public function updatedPurchaseOrderId($purchaseOrderId): void
    {
        if ($this->reference_type !== SupplierReturnReferenceType::PURCHASE_ORDER->value) {
            return;
        }

        if (! $purchaseOrderId) {
            $this->items = [];
            $this->recalculateTotals();

            return;
        }

        $this->loadItemsFromPurchaseOrder((int) $purchaseOrderId);
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
            $this->authorizePermission('supplier.return.edit');
        } else {
            $this->authorizePermission('supplier.return.create');
        }

        if ($this->editMode && $this->returnRecord && ! $this->returnRecord->canEdit()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only draft returns are editable.']);

            return redirect()->route('admin.supplier.returns.index');
        }

        $this->normalizeItems();
        $this->recalculateTotals();

        $validated = $this->validate($this->rules(), $this->messages());

        if (! $this->ensureReferenceConsistency($validated)) {
            return null;
        }

        $this->normalizeItems();
        $this->recalculateTotals();

        $preparedItems = $this->prepareItemsForSave();

        if ($preparedItems === []) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'At least one return item is required.']);

            return null;
        }

        $payload = [
            'supplier_id' => $validated['supplier_id'],
            'return_no' => $validated['return_no'],
            'return_date' => $validated['return_date'],
            'reference_type' => $validated['reference_type'],
            'supplier_bill_id' => $validated['supplier_bill_id'],
            'stock_receive_id' => $validated['stock_receive_id'],
            'purchase_order_id' => $validated['purchase_order_id'],
            'reason' => $validated['reason'],
            'notes' => $validated['notes'],
            'subtotal' => $this->subtotal,
            'total_amount' => $this->total_amount,
            'status' => SupplierReturnStatus::DRAFT->value,
        ];

        try {
            $savedReturn = app(SupplierReturnService::class)->saveReturn(
                payload: $payload,
                items: $preparedItems,
                supplierReturn: $this->returnRecord,
                actorId: (int) auth()->id(),
            );
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);

            return null;
        }

        $this->returnRecord = $savedReturn;
        $this->returnId = $savedReturn->id;
        $this->editMode = true;

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => $wasEditMode ? 'Supplier return updated successfully.' : 'Supplier return created successfully.',
        ]);

        return redirect()->route('admin.supplier.returns.index');
    }

    public function render(): View
    {
        if ($this->editMode) {
            $this->authorizePermission('supplier.return.edit');
        } else {
            $this->authorizePermission('supplier.return.create');
        }

        $bills = SupplierBill::query()
            ->with('supplier:id,name')
            ->whereIn('status', [
                SupplierBillStatus::OPEN->value,
                SupplierBillStatus::PARTIAL->value,
                SupplierBillStatus::PAID->value,
                SupplierBillStatus::OVERDUE->value,
            ])
            ->when($this->supplier_id, fn (Builder $query) => $query->where('supplier_id', $this->supplier_id))
            ->latest('bill_date')
            ->latest('id')
            ->limit(200)
            ->get(['id', 'supplier_id', 'bill_no', 'bill_date', 'total_amount', 'status', 'purchase_order_id', 'stock_receive_id']);

        $stockReceives = StockReceive::query()
            ->with(['supplier:id,name', 'purchaseOrder:id,po_no'])
            ->when($this->supplier_id, fn (Builder $query) => $query->where('supplier_id', $this->supplier_id))
            ->latest('receive_date')
            ->latest('id')
            ->limit(200)
            ->get(['id', 'supplier_id', 'purchase_order_id', 'receive_no', 'receive_date', 'status']);

        $purchaseOrders = PurchaseOrder::query()
            ->with('supplier:id,name')
            ->when($this->supplier_id, fn (Builder $query) => $query->where('supplier_id', $this->supplier_id))
            ->latest('order_date')
            ->latest('id')
            ->limit(200)
            ->get(['id', 'supplier_id', 'po_no', 'order_date', 'status']);

        return view('livewire.admin.supplier.return.supplier-return-form', [
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name', 'code']),
            'bills' => $bills,
            'stockReceives' => $stockReceives,
            'purchaseOrders' => $purchaseOrders,
            'products' => Product::query()->active()->orderBy('name')->get(['id', 'name', 'sku', 'product_unit_id']),
            'units' => ProductUnit::query()->active()->orderBy('name')->get(['id', 'name']),
            'referenceTypes' => SupplierReturnReferenceType::cases(),
            'statusOptions' => [SupplierReturnStatus::DRAFT],
            'manualMode' => $this->isManualMode(),
        ])->layout('layouts.admin.admin');
    }

    protected function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'return_no' => ['required', 'string', 'max:100', Rule::unique('supplier_returns', 'return_no')->ignore($this->returnId)],
            'return_date' => ['required', 'date'],
            'reference_type' => ['required', Rule::enum(SupplierReturnReferenceType::class)],
            'supplier_bill_id' => [
                Rule::requiredIf(fn (): bool => $this->reference_type === SupplierReturnReferenceType::SUPPLIER_BILL->value),
                'nullable',
                'integer',
                'exists:supplier_bills,id',
            ],
            'stock_receive_id' => [
                Rule::requiredIf(fn (): bool => $this->reference_type === SupplierReturnReferenceType::STOCK_RECEIVE->value),
                'nullable',
                'integer',
                'exists:stock_receives,id',
            ],
            'purchase_order_id' => [
                Rule::requiredIf(fn (): bool => $this->reference_type === SupplierReturnReferenceType::PURCHASE_ORDER->value),
                'nullable',
                'integer',
                'exists:purchase_orders,id',
            ],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'gt:0'],
            'status' => ['required', Rule::in([SupplierReturnStatus::DRAFT->value])],
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
            'return_no.required' => 'Return number is required.',
            'return_no.unique' => 'This return number is already in use.',
            'supplier_bill_id.required' => 'Please select a supplier bill for bill-linked return.',
            'stock_receive_id.required' => 'Please select a stock receive for stock-linked return.',
            'purchase_order_id.required' => 'Please select a purchase order for PO-linked return.',
            'items.min' => 'At least one return item is required.',
            'items.*.qty.min' => 'Item quantity must be greater than zero.',
            'total_amount.gt' => 'Return total amount must be greater than zero.',
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
        return $this->reference_type === SupplierReturnReferenceType::MANUAL->value;
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
        $this->total_amount = round(max(0, (float) $this->subtotal), 2);
        $this->status = SupplierReturnStatus::DRAFT->value;
    }

    protected function ensureReferenceConsistency(array &$validated): bool
    {
        if ($validated['reference_type'] === SupplierReturnReferenceType::MANUAL->value) {
            $validated['supplier_bill_id'] = null;
            $validated['stock_receive_id'] = null;
            $validated['purchase_order_id'] = null;

            return true;
        }

        if ($validated['reference_type'] === SupplierReturnReferenceType::SUPPLIER_BILL->value) {
            $bill = SupplierBill::query()
                ->with(['items.product:id,name,sku,product_unit_id', 'supplier:id,name'])
                ->find($validated['supplier_bill_id']);

            if (! $bill) {
                $this->dispatch('toast', ['type' => 'error', 'message' => 'Selected supplier bill is invalid.']);

                return false;
            }

            if ($bill->supplier_id && (int) $validated['supplier_id'] !== (int) $bill->supplier_id) {
                $this->dispatch('toast', ['type' => 'error', 'message' => 'Selected supplier does not match supplier bill.']);

                return false;
            }

            $this->loadItemsFromSupplierBill((int) $bill->id);
            $validated['stock_receive_id'] = $bill->stock_receive_id;
            $validated['purchase_order_id'] = $bill->purchase_order_id;

            return true;
        }

        if ($validated['reference_type'] === SupplierReturnReferenceType::STOCK_RECEIVE->value) {
            $stockReceive = StockReceive::query()
                ->with(['items.product:id,name,sku,product_unit_id', 'supplier:id,name'])
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
            $validated['supplier_bill_id'] = null;
            $validated['purchase_order_id'] = $stockReceive->purchase_order_id;

            return true;
        }

        if ($validated['reference_type'] === SupplierReturnReferenceType::PURCHASE_ORDER->value) {
            $purchaseOrder = PurchaseOrder::query()
                ->with(['items.product:id,name,sku,product_unit_id', 'supplier:id,name'])
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
            $validated['supplier_bill_id'] = null;
            $validated['stock_receive_id'] = null;

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

    protected function loadItemsFromSupplierBill(int $supplierBillId): void
    {
        $bill = SupplierBill::query()
            ->with(['items.product:id,name,sku,product_unit_id', 'supplier:id,name'])
            ->find($supplierBillId);

        if (! $bill) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Supplier bill not found.']);

            return;
        }

        $this->supplier_id = (int) $bill->supplier_id;
        $this->purchase_order_id = $bill->purchase_order_id ? (int) $bill->purchase_order_id : null;
        $this->stock_receive_id = $bill->stock_receive_id ? (int) $bill->stock_receive_id : null;

        $rows = [];

        foreach ($bill->items as $item) {
            $qty = round((float) $item->qty, 3);
            $rate = round((float) $item->rate, 2);
            $productName = $item->product?->name ?? ($item->description ?: 'Bill Item');

            $rows[] = [
                'product_id' => $item->product_id,
                'description' => $productName,
                'qty' => $qty,
                'unit_id' => $item->unit_id ?: $item->product?->product_unit_id,
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

        $this->supplier_id = (int) $stockReceive->supplier_id;
        $this->purchase_order_id = $stockReceive->purchase_order_id ? (int) $stockReceive->purchase_order_id : null;

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

    protected function loadItemsFromPurchaseOrder(int $purchaseOrderId): void
    {
        $purchaseOrder = PurchaseOrder::query()
            ->with(['items.product:id,name,sku,product_unit_id', 'supplier:id,name'])
            ->find($purchaseOrderId);

        if (! $purchaseOrder) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Purchase order not found.']);

            return;
        }

        $this->supplier_id = (int) $purchaseOrder->supplier_id;

        $rows = [];

        foreach ($purchaseOrder->items as $item) {
            $qty = round((float) ($item->approved_quantity ?: $item->quantity), 3);
            $rate = round((float) ($item->approved_unit_price ?: $item->estimated_unit_price), 2);
            $productName = $item->product?->name ?? 'Purchase Order Item';

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
}
