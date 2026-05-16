<?php

namespace App\Livewire\Admin\Inventory\PurchaseOrder;

use App\Enums\Inventory\PurchaseMode;
use App\Enums\Inventory\PurchaseOrderStatus;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockBalance;
use App\Models\StockRequest;
use App\Models\Store;
use App\Models\Supplier;
use App\Services\Inventory\PurchaseOrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PurchaseOrderForm extends Component
{
    use InteractsWithInventoryAccess;

    public ?PurchaseOrder $purchaseOrderRecord = null;

    public ?int $purchaseOrderId = null;

    public bool $editMode = false;

    public bool $isLocked = false;

    public string $po_no = '';

    public string $order_date = '';

    public ?int $store_id = null;

    public ?int $supplier_id = null;

    public string $purchase_mode = 'cash';

    public float|int|string $fund_request_amount = 0;

    public ?string $remarks = null;

    public string $status = 'draft';

    /**
     * @var array<int, array{product_id:int|string|null, quantity:float|int|string, estimated_unit_price:float|int|string, estimated_total_price:float|int|string, remarks:?string}>
     */
    public array $items = [];

    public ?int $selectedItemIndex = null;

    public array $selectedStockRequestIds = [];

    public ?array $quantityDetails = null;

    public ?int $linkedRequestItemIndex = null;

    public ?array $linkedRequestDetails = null;

    public bool $showLinkModal          = false;
    public bool $showQuantityModal      = false;
    public bool $showLinkedDetailsModal = false;

    public function mount(?PurchaseOrder $purchaseOrder = null): void
    {
        if ($purchaseOrder && $purchaseOrder->exists) {
            $this->authorizePermission('inventory.purchase_order.update');

            $this->editMode = true;
            $this->purchaseOrderRecord = $purchaseOrder->load('items');
            $this->purchaseOrderId = $purchaseOrder->id;

            $this->po_no = $purchaseOrder->po_no;
            $this->order_date = optional($purchaseOrder->order_date)->format('Y-m-d') ?: now()->toDateString();
            $this->store_id = $purchaseOrder->store_id;
            $this->supplier_id = $purchaseOrder->supplier_id;
            $this->purchase_mode = $purchaseOrder->purchase_mode?->value ?? PurchaseMode::CASH->value;
            $this->fund_request_amount = (float) $purchaseOrder->fund_request_amount;
            $this->remarks = $purchaseOrder->remarks;
            $this->status = $purchaseOrder->status?->value ?? PurchaseOrderStatus::DRAFT->value;
            $this->isLocked = $purchaseOrder->status?->value !== PurchaseOrderStatus::DRAFT->value;

            $this->ensureStoreAccessible((int) $purchaseOrder->store_id);

            $this->items = $purchaseOrder->items
                ->map(fn ($item): array => [
                    'product_id' => $item->product_id,
                    'quantity' => (float) $item->quantity,
                    'unit' => $item->product->unit ?? '',
                    'estimated_unit_price' => (float) $item->estimated_unit_price,
                    'estimated_total_price' => (float) $item->estimated_total_price,
                    'supplier_id' => $item->supplier_id,
                    'remarks' => $item->remarks,
                    'fund_request_amount' => (float) $item->fund_request_amount,
                    'stock_request_ids' => $purchaseOrder->stockRequests()
                        ->wherePivot('product_id', $item->product_id)
                        ->pluck('stock_requests.id')
                        ->toArray(),
                ])
                ->values()
                ->all();

            if ($this->items === []) {
                $this->items[] = $this->blankItem();
            }

            return;
        }

        $this->authorizePermission('inventory.purchase_order.create');

        $this->po_no = app(PurchaseOrderService::class)->generatePoNo();
        $this->order_date = now()->toDateString();
        $this->purchase_mode = PurchaseMode::CASH->value;
        $this->fund_request_amount = 0;
        $this->items[] = $this->blankItem();

        $copyPurchaseOrderId = (int) request()->integer('copy');
        if ($copyPurchaseOrderId > 0) {
            $copyFrom = PurchaseOrder::query()->with('items')->find($copyPurchaseOrderId);
            if (! $copyFrom) {
                return;
            }

            $this->ensureStoreAccessible((int) $copyFrom->store_id);

            $this->order_date = now()->toDateString();
            $this->store_id = $copyFrom->store_id;
            $this->supplier_id = $copyFrom->supplier_id;
            $this->purchase_mode = $copyFrom->purchase_mode?->value ?? PurchaseMode::CASH->value;
            $this->fund_request_amount = (float) $copyFrom->fund_request_amount;
            $this->remarks = $copyFrom->remarks;
            $this->status = PurchaseOrderStatus::DRAFT->value;

            $this->items = $copyFrom->items
                ->map(fn ($item): array => [
                    'product_id' => $item->product_id,
                    'quantity' => (float) $item->quantity,
                    'unit' => $item->product->unit ?? '',
                    'estimated_unit_price' => (float) $item->estimated_unit_price,
                    'estimated_total_price' => (float) $item->estimated_total_price,
                    'remarks' => $item->remarks,
                    'fund_request_amount' => (float) $item->fund_request_amount,
                ])
                ->values()
                ->all();

            if ($this->items === []) {
                $this->items[] = $this->blankItem();
            }
        }
    }

    public function updatedItems($value, string $name): void
    {
        if (! str_contains($name, '.')) {
            return;
        }

        [$index, $field] = array_pad(explode('.', $name, 2), 2, '');

        // Duplicate product check — fires only when product_id is changed
        if ($field === 'product_id' && $value) {
            $productId = (int) $value;
            foreach ($this->items as $i => $item) {
                if ((int) $i !== (int) $index && (int) ($item['product_id'] ?? 0) === $productId) {
                    // Reset the duplicate selection
                    $this->items[(int) $index]['product_id'] = null;
                    $this->items[(int) $index]['unit']        = '';
                    $this->dispatch('toast', [
                        'type'    => 'error',
                        'message' => 'This item is already added to the purchase order.',
                    ]);
                    return;
                }
            }
        }

        $product = Product::query()->find($this->items[$index]['product_id'] ?? null);
        $this->items[$index]['unit'] = $product->unit ?? '';

        $this->recalculateItem((int) $index);
    }


    public function addItem(): void
    {
        if ($this->isLocked) {
            return;
        }

        $this->items[] = $this->blankItem();
    }

    public function removeItem(int $index): void
    {
        if ($this->isLocked || count($this->items) <= 1) {
            return;
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function saveDraft()
    {

        if ($this->isLocked) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only draft purchase order can be edited.']);

            return;
        }

        try {
            $this->save(PurchaseOrderStatus::DRAFT);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase order saved as draft.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);

            return;
        }

        return redirect()->route('admin.inventory.purchase-orders.index');
    }

    public function submitNow()
    {
        if ($this->isLocked) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only draft purchase order can be edited.']);

            return;
        }

        $this->authorizePermission('inventory.purchase_order.submit');

        try {
            $purchaseOrder = $this->save(PurchaseOrderStatus::DRAFT);
            app(PurchaseOrderService::class)->submitForEngineerApproval($purchaseOrder, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase order submitted for engineer approval.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);

            return;
        }

        return redirect()->route('admin.inventory.purchase-orders.index');
    }

    public function openLinkModal(int $index): void
    {
        $this->selectedItemIndex        = $index;
        $this->selectedStockRequestIds  = [];
        $this->showLinkModal            = true;
    }

    public function closeLinkModal(): void
    {
        $this->selectedItemIndex       = null;
        $this->selectedStockRequestIds = [];
        $this->showLinkModal           = false;
    }

    public function linkStockRequest(): void
    {
        if ($this->selectedItemIndex === null || empty($this->selectedStockRequestIds)) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Please select one or more stock requests to link.']);
            return;
        }

        $item = $this->items[$this->selectedItemIndex] ?? null;
        if (!$item || !$item['product_id']) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Invalid item selected.']);
            return;
        }

        $this->quantityDetails = [
            'itemIndex' => $this->selectedItemIndex,
            'stockRequestIds' => $this->selectedStockRequestIds,
        ];

        $this->closeLinkModal();
        $this->showQuantityModal = true;
    }

    public function closeQuantityModal(): void
    {
        $this->quantityDetails   = null;
        $this->showQuantityModal = false;
    }

    public function openLinkedRequestDetails(int $index): void
    {
        $this->linkedRequestItemIndex   = $index;
        $this->linkedRequestDetails     = $this->buildLinkedRequestDetails($index);
        $this->showLinkedDetailsModal   = true;
    }

    public function closeLinkedRequestDetails(): void
    {
        $this->linkedRequestItemIndex = null;
        $this->linkedRequestDetails   = null;
        $this->showLinkedDetailsModal = false;
    }

    private function buildLinkedRequestDetails(int $index): array
    {

        $item = $this->items[$index] ?? null;
        if(!$item || !$item['product_id'] || isset($item['stock_request_ids']) && empty($item['stock_request_ids'])) {
            return [];
        }

        $stockRequestIds = $item['stock_request_ids'] ?? [];
        if(!is_array($stockRequestIds) || empty($stockRequestIds)) {
            return [];
        }

        $productId = (int) $item['product_id'];
        $product = Product::query()->find($productId);

        $linkedRequests = StockRequest::query()
            ->with(['items' => fn ($query) => $query->where('product_id', $productId), 'requesterStore'])
            ->whereIn('id', $stockRequestIds)
            ->get();

        // $linkedRequests = $this->purchaseOrderRecord->stockRequests()
        //     ->wherePivot('product_id', $productId)
        //     ->with([
        //         'items' => fn ($query) => $query->where('product_id', $productId),
        //         'requesterStore',
        //     ])
        //     ->get();

        $requestDetails = $linkedRequests->map(fn ($request) => [
            'id' => $request->id,
            'request_no' => $request->request_no,
            'status' => $request->status?->label(),
            'requester_name' => $request->requesterStore?->name,
            'requested_quantity' => (float) ($request->items->first()?->approved_quantity ?: $request->items->first()?->quantity ?: 0),
            'fulfilled_quantity' => (float) ($request->items->first()?->fulfilled_quantity ?: 0),
        ])->map(function ($request) {
            $request['remaining_quantity'] = max(0, $request['requested_quantity'] - $request['fulfilled_quantity']);
            return $request;
        });

        $officeStoreIds = Store::query()->office()->pluck('id')->toArray();
        $officeStock = (float) StockBalance::query()
            ->where('product_id', $productId)
            ->whereIn('store_id', $officeStoreIds)
            ->sum('quantity');

        $totalRequested = $requestDetails->sum('requested_quantity');
        $totalFulfilled = $requestDetails->sum('fulfilled_quantity');
        $totalRemaining = $requestDetails->sum('remaining_quantity');
        $needToPurchase = max(0, $totalRemaining - $officeStock);

        return [
            'product_id' => $productId,
            'product_name' => $product?->name,
            'product_unit' => $product?->unit,
            'requests' => $requestDetails->toArray(),
            'total_requested' => $totalRequested,
            'total_fulfilled' => $totalFulfilled,
            'total_remaining' => $totalRemaining,
            'office_stock' => $officeStock,
            'need_to_purchase' => $needToPurchase,
        ];
    }

    public function confirmLink(): void
    {
        if (!$this->quantityDetails) {
            return;
        }


        $details = $this->quantityDetails;
        $itemIndex = $details['itemIndex'] ?? null;
        $stockRequestIds = $details['stockRequestIds'] ?? [];

        if ($itemIndex === null || empty($stockRequestIds)) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Invalid link details.']);
            return;
        }

        $item = $this->items[$itemIndex] ?? null;
        if (!$item || !$item['product_id']) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Invalid item.']);
            return;
        }
        $item['stock_request_ids'] = $stockRequestIds;

        // Auto-fill quantity with "need to purchase" from linked stock requests.
        // The user may still edit the quantity freely after this.
        $productId = (int) $item['product_id'];

        $linkedRequests = StockRequest::query()
            ->with(['items' => fn ($q) => $q->where('product_id', $productId)])
            ->whereIn('id', $stockRequestIds)
            ->get();

        $totalRemaining = $linkedRequests->sum(function ($req) {
            $reqItem = $req->items->first();
            if (! $reqItem) {
                return 0;
            }
            $requested = (float) ($reqItem->approved_quantity ?: $reqItem->quantity ?: 0);
            $fulfilled  = (float) ($reqItem->fulfilled_quantity ?: 0);
            return max(0, $requested - $fulfilled);
        });

        $officeStoreIds = Store::query()->office()->pluck('id')->toArray();
        $officeStock    = (float) StockBalance::query()
            ->where('product_id', $productId)
            ->whereIn('store_id', $officeStoreIds)
            ->sum('quantity');

        $needToPurchase = round(max(0, $totalRemaining - $officeStock), 3);

        $item['quantity'] = $needToPurchase;

        $this->items[$itemIndex] = $item;
        $this->closeQuantityModal();
        $this->openLinkedRequestDetails($itemIndex);
        // dd($item, $stockRequestIds);
        // try {
        //     if ($this->purchaseOrderRecord) {
        //         foreach ($stockRequestIds as $stockRequestId) {
        //             \App\Models\StockRequestPurchaseOrderLink::updateOrCreate([
        //                 'stock_request_id' => $stockRequestId,
        //                 'purchase_order_id' => $this->purchaseOrderRecord->id,
        //                 'product_id' => $item['product_id'],
        //             ], [
        //                 'linked_quantity' => $item['quantity'],
        //                 'remarks' => $item['remarks'] ?? null,
        //             ]);
        //         }
        //     }

        //     $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock request(s) linked successfully.']);
        //     $this->closeQuantityModal();
        //     $this->openLinkedRequestDetails($itemIndex);
        // } catch (\Exception $e) {
        //     $this->dispatch('toast', ['type' => 'error', 'message' => 'Failed to link stock request: ' . $e->getMessage()]);
        // }
    }

    public function render(): View
    {
        if ($this->editMode) {
            $this->authorizePermission('inventory.purchase_order.update');
        } else {
            $this->authorizePermission('inventory.purchase_order.create');
        }

        $storesQuery = Store::query()->active()->orderBy('name');
        if (! $this->canViewAllStores()) {
            $storeIds = $this->getAccessibleStoreIds();
            $storesQuery->whereIn('id', $storeIds === [] ? [0] : $storeIds);
        }

        return view('livewire.admin.inventory.purchase-order.purchase-order-form', [
            'stores' => $storesQuery->get(['id', 'name', 'code', 'type']),
            'suppliers' => Supplier::query()->active()->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->active()->orderBy('name')->get(['id', 'name']),
            'purchaseModes' => PurchaseMode::cases(),
            'isLocked' => $this->isLocked,
            'grandTotal' => $this->grandTotal,
            'availableStockRequests' => $this->availableStockRequests,
        ])->layout('layouts.admin.admin');

    }

    protected function save(PurchaseOrderStatus $status): PurchaseOrder
    {

        if ($this->isLocked) {
            throw new \DomainException('Only draft purchase order can be edited.');
        }

        if ($this->editMode) {
            $this->authorizePermission('inventory.purchase_order.update');
        } else {
            $this->authorizePermission('inventory.purchase_order.create');
        }

        if ($this->store_id) {
            $this->ensureStoreAccessible((int) $this->store_id);
        }

        $this->normalizeItems();

        $validated = $this->validate($this->rules(), $this->messages());

        $this->ensureStoreAccessible((int) $validated['store_id']);

        $purchaseOrder = DB::transaction(function () use ($validated, $status): PurchaseOrder {
            $header = [
                'po_no' => $validated['po_no'],
                'order_date' => $validated['order_date'],
                'store_id' => $validated['store_id'],
                'supplier_id' => $validated['supplier_id'] ?? null,

                'purchase_mode' => $validated['purchase_mode'],
                'fund_request_amount' => $validated['fund_request_amount'],
                'remarks' => $validated['remarks'],
                'status' => $status->value,
                'requested_by' => $this->editMode && $this->purchaseOrderRecord
                    ? $this->purchaseOrderRecord->requested_by
                    : auth()->id(),

            ];

            $record = $this->purchaseOrderRecord;

            if ($this->editMode && $record) {
                if (!in_array($record->status->value, [PurchaseOrderStatus::DRAFT->value], true)) {
                    throw new \DomainException('Only draft purchase order can be edited.');
                }

                $record->update($header);
                $record->items()->delete();
            } else {
                $record = PurchaseOrder::query()->create($header);
                $this->purchaseOrderRecord = $record;
                $this->purchaseOrderId = $record->id;
                $this->editMode = true;
            }

            foreach ($validated['items'] as $item) {
                $record->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? '',
                    'estimated_unit_price' => $item['estimated_unit_price'],
                    'estimated_total_price' => $item['estimated_total_price'],
                    'approved_quantity' => null,
                    'approved_unit_price' => null,
                    'approved_total_price' => null,
                    'remarks' => $item['remarks'] ?? null,
                ]);
                foreach ($item['stock_request_ids'] as $stockRequestId) {
                    \App\Models\StockRequestPurchaseOrderLink::updateOrCreate([
                        'stock_request_id' => $stockRequestId,
                        'purchase_order_id' => $this->purchaseOrderRecord->id,
                        'product_id' => $item['product_id'],
                    ], [
                        'linked_quantity' => $item['quantity'],
                        'remarks' => $item['remarks'] ?? null,
                    ]);
                }
            }


            return $record->refresh();
        });

        return $purchaseOrder;
    }

    protected function rules(): array
    {
        return [
            'po_no' => ['required', 'string', 'max:100', Rule::unique('purchase_orders', 'po_no')->ignore($this->purchaseOrderId)],
            'order_date' => ['required', 'date'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'purchase_mode' => ['required', Rule::in(array_map(fn (PurchaseMode $mode): string => $mode->value, PurchaseMode::cases()))],
            'fund_request_amount' => ['required', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.unit' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.estimated_unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.estimated_total_price' => ['required', 'numeric', 'min:0'],
            'items.*.remarks' => ['nullable', 'string'],
            'items.*.stock_request_ids' => ['nullable', 'array'],
        ];
    }

    protected function messages(): array
    {
        return [
            'store_id.required' => 'Please select a store.',
            'items.*.product_id.required' => 'Please select a product for each row.',
            'items.*.quantity.min' => 'Quantity must be greater than zero.',
        ];
    }

    /**
     * @return array{product_id:null, quantity:float, estimated_unit_price:float, estimated_total_price:float, remarks:null, stock_request_ids:array}
     */
    protected function blankItem(): array
    {
        return [
            'product_id' => null,
            'unit' => '',
            'supplier_id' => null,
            'quantity' => 1,
            'estimated_unit_price' => 0,
            'estimated_total_price' => 0,
            'remarks' => null,
            'stock_request_ids' => [],
        ];
    }

    protected function recalculateItem(int $index): void
    {
        if (! isset($this->items[$index])) {
            return;
        }

        $quantity = (float) ($this->items[$index]['quantity'] ?? 0);
        $unitPrice = (float) ($this->items[$index]['estimated_unit_price'] ?? 0);

        $this->items[$index]['estimated_total_price'] = round($quantity * $unitPrice, 2);
        $this->fund_request_amount = $this->grandTotal;
    }

    protected function normalizeItems(): void
    {
        foreach (array_keys($this->items) as $index) {
            $this->recalculateItem($index);
        }
    }

    public function getGrandTotalProperty(): float
    {
        $total = collect($this->items)->sum(fn (array $item): float => (float) ($item['estimated_total_price'] ?? 0));

        return round($total, 2);
    }

    public function getAvailableStockRequestsProperty()
    {
        if ($this->selectedItemIndex === null) {
            return collect();
        }

        $item = $this->items[$this->selectedItemIndex] ?? null;
        $productId = $item['product_id'] ?? null;

        if (! $productId) {
            return collect();
        }

        return \App\Models\StockRequest::query()
            ->with(['items.product', 'requesterStore'])
            ->whereHas('items', fn ($query) => $query->where('product_id', $productId))
            ->whereIn('status', [
                \App\Enums\Inventory\StockRequestStatus::APPROVED->value,
                \App\Enums\Inventory\StockRequestStatus::PARTIALLY_FULFILLED->value,
            ])
            ->orderBy('request_date', 'desc')
            ->get();
    }

    public function getLinkedRequestsForItemProperty()
    {
        if ($this->selectedItemIndex === null) {
            return collect();
        }

        $item = $this->items[$this->selectedItemIndex] ?? null;
        if (!$item || !$item['product_id']) {
            return collect();
        }

        // If we have an existing purchase order, get linked requests for this product
        if ($this->purchaseOrderRecord) {
            return $this->purchaseOrderRecord->stockRequests()
                ->wherePivot('product_id', $item['product_id'])
                ->with('requesterStore')
                ->get();
        }

        return collect();
    }

    protected function canViewAllStores(): bool
    {
        return $this->hasInventoryWideAccess($this->purchaseOrderGlobalAccessPermissions());
    }
}
