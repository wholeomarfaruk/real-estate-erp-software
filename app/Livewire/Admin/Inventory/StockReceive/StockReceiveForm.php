<?php

namespace App\Livewire\Admin\Inventory\StockReceive;

use App\Enums\Inventory\PurchaseOrderStatus;
use App\Enums\Inventory\StockReceiveStatus;
use App\Enums\Inventory\StoreType;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockReceive;
use App\Models\StockReceiveItem;
use App\Models\Store;
use App\Models\Supplier;
use App\Services\Inventory\StockReceiveService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class StockReceiveForm extends Component
{
    use InteractsWithInventoryAccess;

    public ?StockReceive $stockReceiveRecord = null;

    public ?int $stockReceiveId = null;

    public bool $editMode = false;

    public string $receive_no = '';

    public string $receive_date = '';

    public ?int $purchase_order_id = null;

    public ?int $supplier_id = null;

    public ?string $supplier_voucher = null;
    public ?string $store_receive_number = null;

    public ?int $store_id = null;

    public ?string $remarks = null;

    public string $status = 'draft';

    public bool $isLocked = false;

    public bool $isPostedAdjustmentMode = false;

    public bool $isStructureLocked = false;

    public ?string $lockMessage = null;

    public ?string $poSelectionNotice = null;

    /**
     * @var array<int, float>
     */
    public array $pendingPoItemQuantities = [];

    /**
     * @var array<int, array{id:int|string|null, product_id:int|string|null, purchase_order_item_id:int|string|null, quantity:float|int|string, unit_price:float|int|string, total_price:float|int|string, remarks:?string}>
     */
    public array $items = [];

    public function mount(?StockReceive $stockReceive = null): void
    {
        if ($stockReceive && $stockReceive->exists) {
            $this->authorizePermission('inventory.stock.receive.update');

            $this->editMode = true;
            $this->stockReceiveRecord = $stockReceive->load([
                'items',
                'purchaseOrder.settlement',
            ]);
            $this->stockReceiveId = $stockReceive->id;

            $this->receive_no = $stockReceive->receive_no;
            $this->receive_date = optional($stockReceive->receive_date)->format('Y-m-d') ?: now()->toDateString();
            $this->purchase_order_id = $stockReceive->purchase_order_id;
            $this->supplier_id = $stockReceive->supplier_id;
            $this->supplier_voucher = $stockReceive->supplier_voucher;
            $this->store_id = $stockReceive->store_id;
            $this->remarks = $stockReceive->remarks;
            $this->status = $stockReceive->status?->value ?? StockReceiveStatus::DRAFT->value;

            if ($stockReceive->status === StockReceiveStatus::POSTED) {
                if ($stockReceive->canAdjustPostedReceive()) {
                    $this->isPostedAdjustmentMode = true;
                    $this->isStructureLocked = true;
                } else {
                    $this->isLocked = true;
                    $this->lockMessage = 'This stock receive cannot be edited after settlement is completed.';
                }
            } elseif ($stockReceive->status === StockReceiveStatus::CANCELLED) {
                $this->isLocked = true;
                $this->lockMessage = 'Cancelled stock receive cannot be edited.';
            }

            $this->ensureStoreAccessible((int) $stockReceive->store_id);

            $this->items = $stockReceive->items
                ->map(fn ($item): array => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'purchase_order_item_id' => $item->purchase_order_item_id,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'total_price' => (float) $item->total_price,
                    'remarks' => $item->remarks,
                ])
                ->values()
                ->all();

            if ($this->items === []) {
                $this->items[] = $this->blankItem();
            }

            if ($this->purchase_order_id) {
                $this->pendingPoItemQuantities = $this->pendingQuantitiesForPurchaseOrder((int) $this->purchase_order_id);
            }

            return;
        }

        $this->authorizePermission('inventory.stock.receive.create');

        $this->receive_no = app(StockReceiveService::class)->generateReceiveNo();
        $this->receive_date = now()->toDateString();
        $this->items[] = $this->blankItem();
    }

    public function updatedPurchaseOrderId($purchaseOrderId): void
    {
        if ($this->isLocked || $this->isStructureLocked) {
            return;
        }

        $this->poSelectionNotice = null;

        if (! $purchaseOrderId) {
            $this->supplier_id = null;
            $this->resetLinkedPoItems();

            return;
        }

        try {
            $this->resetLinkedPoItems();
            $this->loadItemsFromPurchaseOrder((int) $purchaseOrderId, $this->supplier_id ? (int) $this->supplier_id : null);
        } catch (\Throwable $throwable) {
            $this->purchase_order_id = null;
            $this->supplier_id = null;
            $this->resetLinkedPoItems();
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function updatedSupplierId($supplierId): void
    {
        if ($this->isLocked || $this->isStructureLocked || ! $this->purchase_order_id) {
            return;
        }

        $this->poSelectionNotice = null;
        $this->resetLinkedPoItems();

        try {
            $this->loadItemsFromPurchaseOrder((int) $this->purchase_order_id, $supplierId ? (int) $supplierId : null);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function addItem(): void
    {
        if ($this->isLocked || $this->isStructureLocked || $this->purchase_order_id) {
            return;
        }

        $this->items[] = $this->blankItem();
    }

    public function removeItem(int $index): void
    {
        if ($this->isLocked || $this->isStructureLocked || count($this->items) <= 1) {
            return;
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updatedItems($value, string $name): void
    {
        if (! str_contains($name, '.')) {
            return;
        }

        [$index] = explode('.', $name);
        $this->recalculateItem((int) $index);
    }

    public function saveChanges()
    {
        if ($this->isLocked) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $this->lockMessage ?: 'This stock receive cannot be edited.']);

            return;
        }

        try {
            if ($this->isPostedAdjustmentMode) {
                $this->savePostedAdjustment();
            } else {
                $this->save(StockReceiveStatus::DRAFT);
            }
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);

            return;
        }

        return redirect()->route('admin.inventory.stock-receives.index');
    }

    public function postNow()
    {
        if ($this->isLocked || $this->isPostedAdjustmentMode) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Posted or cancelled receive cannot be edited.']);

            return;
        }

        $this->authorizePermission('inventory.stock.receive.post');

        try {
            $saved = $this->save(StockReceiveStatus::DRAFT);
            app(StockReceiveService::class)->postReceive($saved, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock receive posted successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);

            return;
        }

        return redirect()->route('admin.inventory.stock-receives.index');
    }

    public function pendingQuantityForIndex(int $index): float
    {
        if (! isset($this->items[$index])) {
            return 0;
        }

        $purchaseOrderItemId = (int) ($this->items[$index]['purchase_order_item_id'] ?? 0);

        if ($purchaseOrderItemId <= 0) {
            return 0;
        }

        $pendingQty = (float) ($this->pendingPoItemQuantities[$purchaseOrderItemId] ?? 0);

        if ($this->isPostedAdjustmentMode) {
            return round($pendingQty + (float) ($this->items[$index]['quantity'] ?? 0), 3);
        }

        return $pendingQty;
    }

    public function render(): View
    {
        if ($this->editMode) {
            $this->authorizePermission('inventory.stock.receive.update');
        } else {
            $this->authorizePermission('inventory.stock.receive.create');
        }

        $storesQuery = Store::query()->active()->office()->orderBy('name');

        if (! $this->canViewAllStores()) {
            $storeIds = $this->getAccessibleStoreIds();
            $storesQuery->whereIn('id', $storeIds === [] ? [0] : $storeIds);
        }

        $purchaseOrdersQuery = $this->selectablePurchaseOrdersQuery()
            ->with(['supplier:id,name', 'store:id,name,code'])
            ->latest('order_date')
            ->latest('id');

            
        return view('livewire.admin.inventory.stock-receive.stock-receive-form', [
            'stores' => $storesQuery->get(['id', 'name', 'code']),
            'suppliers' => Supplier::query()->active()->orderBy('name')->get(['id', 'name', 'contact_person', 'phone']),
            'products' => Product::query()->active()->orderBy('name')->get(['id', 'name', 'sku']),
            'purchaseOrders' => $purchaseOrdersQuery->get(['id', 'po_no', 'store_id', 'status']),
            'grandTotal' => $this->grandTotal,
            'isLocked' => $this->isLocked,
            'isPostedAdjustmentMode' => $this->isPostedAdjustmentMode,
            'isStructureLocked' => $this->isStructureLocked,
            'lockMessage' => $this->lockMessage,
            'poLinked' => (bool) $this->purchase_order_id,
        ])->layout('layouts.admin.admin');
    }

    protected function save(StockReceiveStatus $status): StockReceive
    {
        if ($this->isLocked) {
            throw new \DomainException('Posted or cancelled receive cannot be edited.');
        }

        if ($this->editMode) {
            $this->authorizePermission('inventory.stock.receive.update');
        } else {
            $this->authorizePermission('inventory.stock.receive.create');
        }

        $this->normalizeItems();

        $validated = $this->validate($this->rules(), $this->messages());

        $this->ensureStoreAccessible((int) $validated['store_id']);

        $this->validatePurchaseOrderLink($validated);

        $stockReceive = DB::transaction(function () use ($validated, $status): StockReceive {
            $header = [
                'receive_no' => $validated['receive_no'],
                'receive_date' => $validated['receive_date'],
                'purchase_order_id' => $validated['purchase_order_id'],
                'supplier_id' => $validated['supplier_id'],
                'supplier_voucher' => $validated['supplier_voucher'],
                'store_id' => $validated['store_id'],
                'remarks' => $validated['remarks'],
                'status' => $status->value,
                'created_by' => $this->editMode && $this->stockReceiveRecord
                    ? $this->stockReceiveRecord->created_by
                    : auth()->id(),
            ];

            $record = $this->stockReceiveRecord;

            if ($this->editMode && $record) {
                if (in_array($record->status, [StockReceiveStatus::POSTED, StockReceiveStatus::CANCELLED], true)) {
                    throw new \DomainException('Posted or cancelled receive cannot be edited.');
                }

                $record->update($header);
                $record->items()->delete();
            } else {
                $record = StockReceive::query()->create($header);
                $this->stockReceiveRecord = $record;
                $this->stockReceiveId = $record->id;
                $this->editMode = true;
            }

            foreach ($validated['items'] as $item) {
                $record->items()->create([
                    'product_id' => $item['product_id'],
                    'purchase_order_item_id' => $item['purchase_order_item_id'] ?: null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                    'remarks' => $item['remarks'] ?? null,
                ]);
            }

            return $record->refresh();
        });

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock receive saved successfully.']);

        return $stockReceive;
    }

    protected function rules(): array
    {
        return [
            'receive_no' => ['required', 'string', 'max:100', Rule::unique('stock_receives', 'receive_no')->ignore($this->stockReceiveId)],
            'receive_date' => ['required', 'date'],
            'purchase_order_id' => ['nullable', 'integer', 'exists:purchase_orders,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'supplier_voucher' => ['nullable', 'string', 'max:255'],
            'store_id' => [
                'required',
                'integer',
                Rule::exists('stores', 'id')->where(fn ($query) => $query->where('type', StoreType::OFFICE->value)),
            ],
            'remarks' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer', 'exists:stock_receive_items,id'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.purchase_order_item_id' => ['nullable', 'integer', 'exists:purchase_order_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.total_price' => ['required', 'numeric', 'min:0'],
            'items.*.remarks' => ['nullable', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'store_id.required' => 'Please select an office store.',
            'store_id.exists' => 'Selected store is invalid or not an office store.',
            'items.*.id.required' => 'Invalid stock receive item selected.',
            'items.*.product_id.required' => 'Please select a product for each row.',
        ];
    }

    /**
     * @return array{id:null, product_id:null, purchase_order_item_id:null, quantity:float, unit_price:float, total_price:float, remarks:null}
     */
    protected function blankItem(): array
    {
        return [
            'id' => null,
            'product_id' => null,
            'purchase_order_item_id' => null,
            'quantity' => 1,
            'unit_price' => 0,
            'total_price' => 0,
            'remarks' => null,
        ];
    }

    protected function recalculateItem(int $index): void
    {
        if (! isset($this->items[$index])) {
            return;
        }

        $quantity = (float) ($this->items[$index]['quantity'] ?? 0);
        $unitPrice = (float) ($this->items[$index]['unit_price'] ?? 0);
        $this->items[$index]['total_price'] = round($quantity * $unitPrice, 2);
    }

    protected function normalizeItems(): void
    {
        foreach (array_keys($this->items) as $index) {
            $this->recalculateItem($index);
        }
    }

    protected function savePostedAdjustment(): StockReceive
    {
        $this->authorizePermission('inventory.stock.receive.update');

        if (! $this->stockReceiveRecord) {
            throw new \DomainException('Stock receive record not found.');
        }

        $this->normalizeItems();

        $validated = $this->validate($this->postedAdjustmentRules(), $this->messages());

        $updated = app(StockReceiveService::class)->updatePostedReceive(
            stockReceive: $this->stockReceiveRecord,
            payload: $validated,
            userId: (int) auth()->id()
        );

        $this->stockReceiveRecord = $updated->load([
            'items',
            'purchaseOrder.settlement',
        ]);

        $this->receive_date = optional($updated->receive_date)->format('Y-m-d') ?: now()->toDateString();
        $this->supplier_voucher = $updated->supplier_voucher;
        $this->remarks = $updated->remarks;
        $this->items = $updated->items
            ->map(fn ($item): array => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'purchase_order_item_id' => $item->purchase_order_item_id,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) $item->total_price,
                'remarks' => $item->remarks,
            ])
            ->values()
            ->all();

        if ($this->purchase_order_id) {
            $this->pendingPoItemQuantities = $this->pendingQuantitiesForPurchaseOrder((int) $this->purchase_order_id);
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Posted stock receive updated successfully.']);

        return $updated;
    }

    protected function postedAdjustmentRules(): array
    {
        return [
            'receive_date' => ['required', 'date'],
            'supplier_voucher' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:stock_receive_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.remarks' => ['nullable', 'string'],
        ];
    }

    protected function validatePurchaseOrderLink(array &$validated): void
    {
        if (empty($validated['purchase_order_id'])) {
            foreach ($validated['items'] as $index => $item) {
                $validated['items'][$index]['purchase_order_item_id'] = null;
            }

            return;
        }

        $purchaseOrder = PurchaseOrder::query()
            ->with('items')
            ->findOrFail($validated['purchase_order_id']);

        if (! $this->canViewAllStores()) {
            $this->ensureStoreAccessible((int) $purchaseOrder->store_id);
        }

        if (! in_array($purchaseOrder->status, [
            PurchaseOrderStatus::PENDING_ACCOUNTS,
            PurchaseOrderStatus::APPROVED,
            PurchaseOrderStatus::PARTIALLY_RECEIVED,
        ], true)) {
            throw new \DomainException('Only pending accounts, approved or partially received purchase order can be received against.');
        }

        $pendingQuantities = $this->pendingQuantitiesForPurchaseOrder((int) $purchaseOrder->id);
        $pendingSupplierIds = $this->supplierIdsFromPendingItems($purchaseOrder, $pendingQuantities);

        if (count($pendingSupplierIds) > 1 && ! $validated['supplier_id']) {
            throw new \DomainException('Please select supplier first.');
        }

        if (count($pendingSupplierIds) === 1 && ! $validated['supplier_id']) {
            $validated['supplier_id'] = $pendingSupplierIds[0];
        }

        if ($validated['supplier_id'] && ! in_array((int) $validated['supplier_id'], $pendingSupplierIds, true)) {
            throw new \DomainException('Selected supplier does not match the linked purchase order supplier.');
        }

        $poItems = $purchaseOrder->items->keyBy('id');
        $requestedByPoItem = [];

        foreach ($validated['items'] as $item) {
            $purchaseOrderItemId = (int) ($item['purchase_order_item_id'] ?? 0);
            if ($purchaseOrderItemId <= 0) {
                throw new \DomainException('Each receive row must be linked to a purchase order item when PO is selected.');
            }

            $poItem = $poItems->get($purchaseOrderItemId);
            if (! $poItem) {
                throw new \DomainException('A selected purchase order item does not belong to this purchase order.');
            }

            if ((int) $poItem->product_id !== (int) $item['product_id']) {
                throw new \DomainException('Product mismatch found between receive item and purchase order item.');
            }

            if (! empty($validated['supplier_id']) && (int) ($poItem->supplier_id ?? 0) !== (int) $validated['supplier_id']) {
                throw new \DomainException('Only selected supplier items are allowed for this purchase order.');
            }

            $requestedByPoItem[$purchaseOrderItemId] = ($requestedByPoItem[$purchaseOrderItemId] ?? 0) + (float) $item['quantity'];
        }

        foreach ($requestedByPoItem as $purchaseOrderItemId => $requestedQty) {
            $pendingQty = (float) ($pendingQuantities[$purchaseOrderItemId] ?? 0);

            if ($requestedQty > $pendingQty + 0.0001) {
                $poItem = $poItems->get($purchaseOrderItemId);
                throw new \DomainException(
                    'Receive quantity exceeds pending quantity for product '
                    .($poItem->product?->name ?? 'item').'. Pending: '.number_format($pendingQty, 3)
                );
            }
        }

        $this->pendingPoItemQuantities = $pendingQuantities;
    }

    protected function loadItemsFromPurchaseOrder(int $purchaseOrderId, ?int $supplierId = null): void
    {

        $purchaseOrder = $this->selectablePurchaseOrdersQuery()
            ->with(['items.product:id,name,sku', 'items.supplier:id,name,code', 'store:id,type'])
            ->find($purchaseOrderId);

        if (! $purchaseOrder) {
            throw new \DomainException('Selected purchase order is not available for stock receive.');
        }


        $pendingQuantities = $this->pendingQuantitiesForPurchaseOrder($purchaseOrderId);
        $pendingSupplierIds = $this->supplierIdsFromPendingItems($purchaseOrder, $pendingQuantities);

        if (count($pendingSupplierIds) === 1 && ! $supplierId) {
            $supplierId = $pendingSupplierIds[0];
            $this->supplier_id = $supplierId;
        }

        if (count($pendingSupplierIds) > 1 && ! $supplierId) {
            $this->poSelectionNotice = 'Please select supplier first.';
            $this->dispatch('toast', ['type' => 'warning', 'message' => 'Please select supplier first.']);

            return;
        }

        if ($supplierId && ! in_array((int) $supplierId, $pendingSupplierIds, true)) {
            $this->poSelectionNotice = 'Please select supplier first.';
            $this->dispatch('toast', ['type' => 'warning', 'message' => 'Please select supplier first.']);

            return;
        }

        $items = [];

        foreach ($purchaseOrder->items as $item) {
            $pendingQty = (float) ($pendingQuantities[$item->id] ?? 0);

            if ($pendingQty <= 0) {
                continue;
            }

            if ($supplierId && (int) ($item->supplier_id ?? 0) !== (int) $supplierId) {
                continue;
            }

            $unitPrice = (float) ($item->approved_unit_price ?? $item->estimated_unit_price ?? 0);

            $items[] = [
                'product_id' => $item->product_id,
                'purchase_order_item_id' => $item->id,
                'quantity' => $pendingQty,
                'unit_price' => $unitPrice,
                'total_price' => round($pendingQty * $unitPrice, 2),
                'remarks' => $item->remarks,
            ];
        }

        if ($items === []) {
            throw new \DomainException('All selected purchase order items are already fully received.');
        }

        $this->pendingPoItemQuantities = $pendingQuantities;
        $this->items = $items;

        if (! $this->store_id && $purchaseOrder->store?->type === StoreType::OFFICE) {
            $this->store_id = (int) $purchaseOrder->store_id;
        }
    }

    /**
     * @return array<int, float>
     */
    protected function pendingQuantitiesForPurchaseOrder(int $purchaseOrderId): array
    {
        $purchaseOrder = PurchaseOrder::query()->with('items')->findOrFail($purchaseOrderId);
        $poItemIds = $purchaseOrder->items->pluck('id')->all();

        $receivedByItem = StockReceiveItem::query()
            ->selectRaw('purchase_order_item_id, SUM(quantity) as received_quantity')
            ->whereIn('purchase_order_item_id', $poItemIds === [] ? [0] : $poItemIds)
            ->whereHas('stockReceive', function (Builder $builder): void {
                $builder->where('status', StockReceiveStatus::POSTED->value);
            })
            ->groupBy('purchase_order_item_id')
            ->pluck('received_quantity', 'purchase_order_item_id');

        $pending = [];

        foreach ($purchaseOrder->items as $item) {
            $requiredQty = (float) ($item->approved_quantity ?: $item->quantity);
            $receivedQty = (float) ($receivedByItem[$item->id] ?? 0);
            $pending[$item->id] = max(0, round($requiredQty - $receivedQty, 3));
        }

        return $pending;
    }

    protected function selectablePurchaseOrdersQuery(): Builder
    {
        $query = PurchaseOrder::query()
            ->where(function (Builder $builder): void {
                $builder->whereIn('status', [
                    PurchaseOrderStatus::PENDING_ACCOUNTS->value,
                    PurchaseOrderStatus::APPROVED->value,
                    PurchaseOrderStatus::PARTIALLY_RECEIVED->value,
                ]);

                if ($this->purchase_order_id) {
                    $builder->orWhere('id', (int) $this->purchase_order_id);
                }
            });

        if ($this->supplier_id) {
            $selectedSupplierId = (int) $this->supplier_id;
            $query->whereHas('items', function (Builder $builder) use ($selectedSupplierId): void {
                $builder->where('supplier_id', $selectedSupplierId);
            });
        }

        if (! $this->canViewAllStores()) {
            $storeIds = $this->getAccessibleStoreIds();
            $query->whereIn('store_id', $storeIds === [] ? [0] : $storeIds);
        }

        return $query;
    }

    protected function canViewAllStores(): bool
    {
        return $this->hasInventoryWideAccess($this->stockReceiveGlobalAccessPermissions());
    }

    protected function supplierIdsFromPendingItems(PurchaseOrder $purchaseOrder, array $pendingQuantities): array
    {
        return $purchaseOrder->items
            ->filter(fn ($item) => (float) ($pendingQuantities[$item->id] ?? 0) > 0)
            ->pluck('supplier_id')
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    protected function resetLinkedPoItems(): void
    {
        $this->pendingPoItemQuantities = [];
        $this->items = [$this->blankItem()];
    }

    public function getGrandTotalProperty(): float
    {
        $total = collect($this->items)->sum(fn (array $item): float => (float) ($item['total_price'] ?? 0));

        return round($total, 2);
    }
}
