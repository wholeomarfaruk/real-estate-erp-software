<?php

namespace App\Livewire\Admin\Inventory\PurchaseReturn;

use App\Enums\Inventory\PurchaseReturnStatus;
use App\Enums\Inventory\StockReceiveStatus;
use App\Enums\Inventory\StoreType;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\StockReceive;
use App\Models\Store;
use App\Models\Supplier;
use App\Services\Inventory\PurchaseReturnService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PurchaseReturnForm extends Component
{
    use InteractsWithInventoryAccess;

    public ?PurchaseReturn $purchaseReturnRecord = null;

    public ?int $purchaseReturnId = null;

    public bool $editMode = false;

    public bool $isLocked = false;

    public string $return_no = '';

    public string $return_date = '';

    public ?int $supplier_id = null;

    public ?int $store_id = null;

    public ?int $purchase_order_id = null;

    public ?int $stock_receive_id = null;

    public ?string $reason = null;

    public ?string $remarks = null;

    public string $status = 'draft';

    /**
     * @var array<int, array{
     *   stock_receive_item_id:int|string|null,
     *   purchase_order_item_id:int|string|null,
     *   product_id:int|string|null,
     *   product_name:string,
     *   product_sku:?string,
     *   original_quantity:float,
     *   already_returned_quantity:float,
     *   returnable_quantity:float,
     *   available_quantity:float,
     *   max_return_quantity:float,
     *   quantity:float|int|string,
     *   unit_price:float,
     *   total_price:float,
     *   remarks:?string
     * }>
     */
    public array $items = [];

    public function mount(?PurchaseReturn $purchaseReturn = null): void
    {
        if ($purchaseReturn && $purchaseReturn->exists) {
            $this->authorizePermission('inventory.purchase_return.update');

            $this->editMode = true;
            $this->purchaseReturnRecord = $purchaseReturn->load([
                'items',
                'stockReceive.items.product:id,name,sku',
                'stockReceive.items.purchaseOrderItem:id,purchase_order_id,product_id',
            ]);
            $this->purchaseReturnId = $purchaseReturn->id;

            $this->return_no = $purchaseReturn->return_no;
            $this->return_date = optional($purchaseReturn->return_date)->format('Y-m-d') ?: now()->toDateString();
            $this->supplier_id = $purchaseReturn->supplier_id;
            $this->store_id = $purchaseReturn->store_id;
            $this->purchase_order_id = $purchaseReturn->purchase_order_id;
            $this->stock_receive_id = $purchaseReturn->stock_receive_id;
            $this->reason = $purchaseReturn->reason;
            $this->remarks = $purchaseReturn->remarks;
            $this->status = $purchaseReturn->status?->value ?? PurchaseReturnStatus::DRAFT->value;
            $this->isLocked = in_array($purchaseReturn->status, [PurchaseReturnStatus::POSTED, PurchaseReturnStatus::CANCELLED], true);

            $this->ensureStoreAccessible((int) $purchaseReturn->store_id);

            if ($this->stock_receive_id) {
                $this->hydrateItemsFromStockReceive((int) $this->stock_receive_id);

                $existingByReceiveItem = $purchaseReturn->items
                    ->keyBy(fn ($item) => (int) $item->stock_receive_item_id);

                foreach ($this->items as $index => $row) {
                    $stockReceiveItemId = (int) ($row['stock_receive_item_id'] ?? 0);
                    $existing = $existingByReceiveItem->get($stockReceiveItemId);

                    if (! $existing) {
                        continue;
                    }

                    $quantity = round((float) $existing->quantity, 3);
                    $unitPrice = round((float) $row['unit_price'], 2);

                    $this->items[$index]['quantity'] = $quantity;
                    $this->items[$index]['remarks'] = $existing->remarks;
                    $this->items[$index]['total_price'] = round($quantity * $unitPrice, 2);
                }
            }

            return;
        }

        $this->authorizePermission('inventory.purchase_return.create');

        $this->return_no = app(PurchaseReturnService::class)->generateReturnNo();
        $this->return_date = now()->toDateString();
    }

    public function updatedSupplierId(): void
    {
        if ($this->isLocked) {
            return;
        }

        if (! $this->stock_receive_id) {
            return;
        }

        if (! $this->isSelectedStockReceiveStillValid()) {
            $this->stock_receive_id = null;
            $this->purchase_order_id = null;
            $this->items = [];
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Selected stock receive does not match supplier/store filter anymore.']);
        }
    }

    public function updatedStoreId($storeId): void
    {
        if ($this->isLocked) {
            return;
        }

        if (! $storeId) {
            $this->items = [];

            return;
        }

        if (! $this->canViewAllStores()) {
            $this->ensureStoreAccessible((int) $storeId);
        }

        $storeType = Store::query()->whereKey($storeId)->value('type');
        if ($storeType !== StoreType::OFFICE->value) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Purchase return is allowed only for office stores.']);
        }

        if (! $this->stock_receive_id) {
            return;
        }

        if (! $this->isSelectedStockReceiveStillValid()) {
            $this->stock_receive_id = null;
            $this->purchase_order_id = null;
            $this->items = [];
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Selected stock receive does not match supplier/store filter anymore.']);
        }
    }

    public function updatedStockReceiveId($stockReceiveId): void
    {
        if ($this->isLocked) {
            return;
        }

        if (! $stockReceiveId) {
            $this->purchase_order_id = null;
            $this->items = [];

            return;
        }

        try {
            $this->hydrateItemsFromStockReceive((int) $stockReceiveId);
        } catch (\Throwable $throwable) {
            $this->stock_receive_id = null;
            $this->purchase_order_id = null;
            $this->items = [];
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function updatedItems($value, string $name): void
    {
        if (! str_contains($name, '.')) {
            return;
        }

        [$index] = explode('.', $name);
        $this->recalculateItem((int) $index);
    }

    public function saveDraft()
    {
        if ($this->isLocked) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Posted or cancelled purchase return cannot be edited.']);

            return;
        }

        try {
            $this->save(PurchaseReturnStatus::DRAFT);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);

            return;
        }

        return redirect()->route('admin.inventory.purchase-returns.index');
    }

    public function postNow()
    {
        if ($this->isLocked) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Posted or cancelled purchase return cannot be edited.']);

            return;
        }

        $this->authorizePermission('inventory.purchase_return.post');

        try {
            $saved = $this->save(PurchaseReturnStatus::DRAFT);
            app(PurchaseReturnService::class)->postReturn($saved, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase return posted successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);

            return;
        }

        return redirect()->route('admin.inventory.purchase-returns.index');
    }

    public function render(): View
    {
        if ($this->editMode) {
            $this->authorizePermission('inventory.purchase_return.update');
        } else {
            $this->authorizePermission('inventory.purchase_return.create');
        }

        $storesQuery = Store::query()->active()->office()->orderBy('name');
        if (! $this->canViewAllStores()) {
            $storeIds = $this->getAccessibleStoreIds();
            $storesQuery->whereIn('id', $storeIds === [] ? [0] : $storeIds);
        }

        $purchaseOrdersQuery = PurchaseOrder::query()
            ->with(['supplier:id,name', 'store:id,name,code'])
            ->latest('order_date')
            ->latest('id')
            ->when($this->supplier_id, fn (Builder $builder): Builder => $builder->where('supplier_id', $this->supplier_id))
            ->when($this->store_id, fn (Builder $builder): Builder => $builder->where('store_id', $this->store_id))
            ->when($this->purchase_order_id, fn (Builder $builder): Builder => $builder->orWhereKey($this->purchase_order_id));

        if (! $this->canViewAllStores()) {
            $storeIds = $this->getAccessibleStoreIds();
            $purchaseOrdersQuery->whereIn('store_id', $storeIds === [] ? [0] : $storeIds);
        }

        return view('livewire.admin.inventory.purchase-return.purchase-return-form', [
            'stores' => $storesQuery->get(['id', 'name', 'code']),
            'suppliers' => Supplier::query()->active()->orderBy('name')->get(['id', 'name', 'phone']),
            'purchaseOrders' => $purchaseOrdersQuery->limit(200)->get(['id', 'po_no', 'supplier_id', 'store_id', 'status']),
            'stockReceives' => $this->selectableStockReceivesQuery()
                ->with(['supplier:id,name', 'store:id,name,code', 'purchaseOrder:id,po_no'])
                ->orderByDesc('receive_date')
                ->orderByDesc('id')
                ->limit(300)
                ->get(['id', 'receive_no', 'receive_date', 'supplier_id', 'store_id', 'purchase_order_id']),
            'isLocked' => $this->isLocked,
            'grandTotal' => $this->grandTotal,
        ])->layout('layouts.admin.admin');
    }

    protected function save(PurchaseReturnStatus $status): PurchaseReturn
    {
        if ($this->isLocked) {
            throw new \DomainException('Posted or cancelled purchase return cannot be edited.');
        }

        if ($this->editMode) {
            $this->authorizePermission('inventory.purchase_return.update');
        } else {
            $this->authorizePermission('inventory.purchase_return.create');
        }

        if ($this->store_id) {
            $this->ensureStoreAccessible((int) $this->store_id);
        }

        $validated = $this->validate($this->rules(), $this->messages());

        $this->ensureStoreAccessible((int) $validated['store_id']);

        $store = Store::query()->findOrFail((int) $validated['store_id']);
        if ($store->type !== StoreType::OFFICE) {
            throw new \DomainException('Purchase return is allowed only for office stores.');
        }

        $stockReceive = $this->resolveSelectedStockReceive(
            stockReceiveId: (int) $validated['stock_receive_id'],
            supplierId: (int) $validated['supplier_id'],
            storeId: (int) $validated['store_id']
        );

        if ($validated['purchase_order_id'] && $stockReceive->purchase_order_id && (int) $validated['purchase_order_id'] !== (int) $stockReceive->purchase_order_id) {
            throw new \DomainException('Selected purchase order does not match the selected stock receive.');
        }

        if (! $validated['purchase_order_id'] && $stockReceive->purchase_order_id) {
            $validated['purchase_order_id'] = (int) $stockReceive->purchase_order_id;
            $this->purchase_order_id = (int) $stockReceive->purchase_order_id;
        }

        $preparedItems = $this->prepareSelectedItems($stockReceive, (int) $validated['store_id']);

        if ($preparedItems === []) {
            throw new \DomainException('Please enter return quantity for at least one item.');
        }

        Validator::make(
            ['items' => $preparedItems],
            $this->selectedItemsRules(),
            $this->selectedItemsMessages()
        )->validate();

        $purchaseReturn = DB::transaction(function () use ($validated, $preparedItems, $status): PurchaseReturn {
            $header = [
                'return_no' => $validated['return_no'],
                'return_date' => $validated['return_date'],
                'supplier_id' => $validated['supplier_id'],
                'store_id' => $validated['store_id'],
                'purchase_order_id' => $validated['purchase_order_id'],
                'stock_receive_id' => $validated['stock_receive_id'],
                'reason' => $validated['reason'],
                'remarks' => $validated['remarks'],
                'status' => $status->value,
                'created_by' => $this->editMode && $this->purchaseReturnRecord
                    ? $this->purchaseReturnRecord->created_by
                    : auth()->id(),
            ];

            $record = $this->purchaseReturnRecord;

            if ($this->editMode && $record) {
                if (in_array($record->status, [PurchaseReturnStatus::POSTED, PurchaseReturnStatus::CANCELLED], true)) {
                    throw new \DomainException('Posted or cancelled purchase return cannot be edited.');
                }

                $record->update($header);
                $record->items()->delete();
            } else {
                $record = PurchaseReturn::query()->create($header);
                $this->purchaseReturnRecord = $record;
                $this->purchaseReturnId = $record->id;
                $this->editMode = true;
            }

            foreach ($preparedItems as $item) {
                $record->items()->create([
                    'stock_receive_item_id' => $item['stock_receive_item_id'],
                    'purchase_order_item_id' => $item['purchase_order_item_id'],
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                    'remarks' => $item['remarks'] ?? null,
                ]);
            }

            return $record->refresh();
        });

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase return saved successfully.']);

        return $purchaseReturn;
    }

    protected function rules(): array
    {
        return [
            'return_no' => ['required', 'string', 'max:100', Rule::unique('purchase_returns', 'return_no')->ignore($this->purchaseReturnId)],
            'return_date' => ['required', 'date'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'store_id' => [
                'required',
                'integer',
                Rule::exists('stores', 'id')->where(fn ($query) => $query->where('type', StoreType::OFFICE->value)),
            ],
            'purchase_order_id' => ['nullable', 'integer', 'exists:purchase_orders,id'],
            'stock_receive_id' => ['required', 'integer', 'exists:stock_receives,id'],
            'reason' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'store_id.required' => 'Please select an office store.',
            'store_id.exists' => 'Selected store is invalid or not an office store.',
            'stock_receive_id.required' => 'Please select a linked stock receive document.',
        ];
    }

    protected function selectedItemsRules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.stock_receive_item_id' => ['required', 'integer', 'exists:stock_receive_items,id'],
            'items.*.purchase_order_item_id' => ['nullable', 'integer', 'exists:purchase_order_items,id'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.remarks' => ['nullable', 'string'],
        ];
    }

    protected function selectedItemsMessages(): array
    {
        return [
            'items.required' => 'Please enter at least one item quantity for return.',
            'items.min' => 'Please enter at least one item quantity for return.',
            'items.*.quantity.min' => 'Return quantity must be at least 0.001.',
        ];
    }

    protected function recalculateItem(int $index): void
    {
        if (! isset($this->items[$index])) {
            return;
        }

        $quantity = max(0, round((float) ($this->items[$index]['quantity'] ?? 0), 3));
        $unitPrice = round((float) ($this->items[$index]['unit_price'] ?? 0), 2);

        $this->items[$index]['quantity'] = $quantity;
        $this->items[$index]['total_price'] = round($quantity * $unitPrice, 2);
    }

    protected function isSelectedStockReceiveStillValid(): bool
    {
        if (! $this->stock_receive_id) {
            return true;
        }

        return $this->selectableStockReceivesQuery()->whereKey($this->stock_receive_id)->exists();
    }

    protected function resolveSelectedStockReceive(int $stockReceiveId, int $supplierId, int $storeId): StockReceive
    {
        $stockReceive = StockReceive::query()
            ->with('items')
            ->findOrFail($stockReceiveId);

        if ($stockReceive->status !== StockReceiveStatus::POSTED) {
            throw new \DomainException('Purchase return must be linked with a posted stock receive.');
        }

        if ((int) $stockReceive->supplier_id !== $supplierId) {
            throw new \DomainException('Selected stock receive does not belong to the selected supplier.');
        }

        if ((int) $stockReceive->store_id !== $storeId) {
            throw new \DomainException('Selected stock receive does not belong to the selected store.');
        }

        return $stockReceive;
    }

    protected function hydrateItemsFromStockReceive(int $stockReceiveId): void
    {
        $stockReceive = $this->selectableStockReceivesQuery()
            ->with([
                'items.product:id,name,sku',
                'items.purchaseOrderItem:id,purchase_order_id,product_id',
            ])
            ->find($stockReceiveId);

        if (! $stockReceive) {
            throw new \DomainException('Selected stock receive is not available for purchase return.');
        }

        if (! $this->supplier_id) {
            $this->supplier_id = (int) $stockReceive->supplier_id;
        }

        if (! $this->store_id) {
            $this->store_id = (int) $stockReceive->store_id;
        }

        if (! $this->purchase_order_id && $stockReceive->purchase_order_id) {
            $this->purchase_order_id = (int) $stockReceive->purchase_order_id;
        }

        $service = app(PurchaseReturnService::class);

        $stockReceiveItemIds = $stockReceive->items->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $alreadyReturnedMap = $service->postedReturnedQtyMap(
            stockReceiveItemIds: $stockReceiveItemIds,
            excludePurchaseReturnId: $this->purchaseReturnId
        );

        $rows = [];

        foreach ($stockReceive->items as $receiveItem) {
            $productId = (int) $receiveItem->product_id;
            $originalQty = round((float) $receiveItem->quantity, 3);
            $alreadyReturnedQty = round((float) ($alreadyReturnedMap[(int) $receiveItem->id] ?? 0), 3);
            $returnableQty = $service->calculateReturnableQty($originalQty, $alreadyReturnedQty);
            $availableQty = $service->availableQty((int) $stockReceive->store_id, $productId);
            $maxReturnQty = $service->calculateMaxReturnQty($returnableQty, $availableQty);
            $unitPrice = round((float) $receiveItem->unit_price, 2);

            $rows[] = [
                'stock_receive_item_id' => (int) $receiveItem->id,
                'purchase_order_item_id' => $receiveItem->purchase_order_item_id ? (int) $receiveItem->purchase_order_item_id : null,
                'product_id' => $productId,
                'product_name' => $receiveItem->product?->name ?? 'N/A',
                'product_sku' => $receiveItem->product?->sku,
                'original_quantity' => $originalQty,
                'already_returned_quantity' => $alreadyReturnedQty,
                'returnable_quantity' => $returnableQty,
                'available_quantity' => $availableQty,
                'max_return_quantity' => $maxReturnQty,
                'quantity' => 0,
                'unit_price' => $unitPrice,
                'total_price' => 0,
                'remarks' => null,
            ];
        }

        if ($rows === []) {
            throw new \DomainException('Selected stock receive has no items available for return.');
        }

        $this->items = $rows;
    }

    /**
     * @return array<int, array{stock_receive_item_id:int,purchase_order_item_id:int|null,product_id:int,quantity:float,unit_price:float,total_price:float,remarks:?string}>
     */
    protected function prepareSelectedItems(StockReceive $stockReceive, int $storeId): array
    {
        $service = app(PurchaseReturnService::class);

        $receiveItemsById = $stockReceive->items
            ->keyBy(fn ($item) => (int) $item->id);

        $receiveItemIds = $receiveItemsById->keys()->all();
        $alreadyReturnedMap = $service->postedReturnedQtyMap($receiveItemIds, $this->purchaseReturnId);

        $prepared = [];
        $requiredByProduct = [];
        $availableByProduct = [];

        foreach ($this->items as $index => $item) {
            $quantity = round((float) ($item['quantity'] ?? 0), 3);
            if ($quantity <= 0) {
                $this->items[$index]['total_price'] = 0;

                continue;
            }

            $stockReceiveItemId = (int) ($item['stock_receive_item_id'] ?? 0);
            $receiveItem = $receiveItemsById->get($stockReceiveItemId);

            if (! $receiveItem) {
                throw new \DomainException('One of the selected return items is invalid for this stock receive.');
            }

            $productId = (int) ($item['product_id'] ?? 0);
            if ($productId !== (int) $receiveItem->product_id) {
                throw new \DomainException('Product mismatch found between selected return row and source receive item.');
            }

            $originalQty = (float) $receiveItem->quantity;
            $alreadyReturnedQty = (float) ($alreadyReturnedMap[$stockReceiveItemId] ?? 0);
            $returnableQty = $service->calculateReturnableQty($originalQty, $alreadyReturnedQty);
            $availableQty = $availableByProduct[$productId]
                ??= $service->availableQty($storeId, $productId);
            $maxReturnQty = $service->calculateMaxReturnQty($returnableQty, $availableQty);

            if ($quantity > $maxReturnQty + 0.0001) {
                throw new \DomainException(
                    ($receiveItem->product?->name ?? 'Selected product').
                    ' return quantity exceeds max allowed. Max: '.number_format($maxReturnQty, 3)
                );
            }

            $requiredByProduct[$productId] = ($requiredByProduct[$productId] ?? 0) + $quantity;

            $unitPrice = round((float) $receiveItem->unit_price, 2);
            $totalPrice = round($quantity * $unitPrice, 2);

            $this->items[$index]['quantity'] = $quantity;
            $this->items[$index]['unit_price'] = $unitPrice;
            $this->items[$index]['total_price'] = $totalPrice;
            $this->items[$index]['purchase_order_item_id'] = $receiveItem->purchase_order_item_id
                ? (int) $receiveItem->purchase_order_item_id
                : null;

            $prepared[] = [
                'stock_receive_item_id' => $stockReceiveItemId,
                'purchase_order_item_id' => $receiveItem->purchase_order_item_id
                    ? (int) $receiveItem->purchase_order_item_id
                    : null,
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'remarks' => $item['remarks'] ?? null,
            ];
        }

        foreach ($requiredByProduct as $productId => $requiredQty) {
            $availableQty = (float) ($availableByProduct[$productId] ?? 0);

            if ($requiredQty > $availableQty + 0.0001) {
                throw new \DomainException(
                    'Return quantity exceeds available stock for one or more products. Available: '
                    .number_format($availableQty, 3).', Required: '.number_format($requiredQty, 3)
                );
            }
        }

        return $prepared;
    }

    protected function selectableStockReceivesQuery(): Builder
    {
        $query = StockReceive::query()
            ->whereHas('store', fn (Builder $builder): Builder => $builder->where('type', StoreType::OFFICE->value))
            ->where(function (Builder $builder): void {
                $builder->where('status', StockReceiveStatus::POSTED->value);

                if ($this->stock_receive_id) {
                    $builder->orWhereKey($this->stock_receive_id);
                }
            })
            ->when($this->supplier_id, fn (Builder $builder): Builder => $builder->where('supplier_id', $this->supplier_id))
            ->when($this->store_id, fn (Builder $builder): Builder => $builder->where('store_id', $this->store_id))
            ->when($this->purchase_order_id, fn (Builder $builder): Builder => $builder->where('purchase_order_id', $this->purchase_order_id));

        if (! $this->canViewAllStores()) {
            $storeIds = $this->getAccessibleStoreIds();
            $query->whereIn('store_id', $storeIds === [] ? [0] : $storeIds);
        }

        return $query;
    }

    public function getGrandTotalProperty(): float
    {
        $total = collect($this->items)->sum(fn (array $item): float => (float) ($item['total_price'] ?? 0));

        return round($total, 2);
    }
}
