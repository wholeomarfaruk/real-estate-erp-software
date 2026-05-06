<?php

namespace App\Livewire\Admin\Inventory\PurchaseOrder;

use App\Enums\Inventory\PurchaseMode;
use App\Enums\Inventory\PurchaseOrderStatus;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\Product;
use App\Models\PurchaseOrder;
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
            $this->isLocked = $purchaseOrder->status !== PurchaseOrderStatus::DRAFT;

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
                    'supplier_id' => $item->supplier_id,
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

        [$index] = explode('.', $name);
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
                if ($record->status !== PurchaseOrderStatus::DRAFT) {
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
                    'supplier_id' => $item['supplier_id'] ?? null,
                    'estimated_unit_price' => $item['estimated_unit_price'],
                    'estimated_total_price' => $item['estimated_total_price'],
                    'approved_quantity' => null,
                    'approved_unit_price' => null,
                    'approved_total_price' => null,
                    'remarks' => $item['remarks'] ?? null,
                ]);
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
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'purchase_mode' => ['required', Rule::in(array_map(fn (PurchaseMode $mode): string => $mode->value, PurchaseMode::cases()))],
            'fund_request_amount' => ['required', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'items.*.unit' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.estimated_unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.estimated_total_price' => ['required', 'numeric', 'min:0'],
            'items.*.remarks' => ['nullable', 'string'],
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
     * @return array{product_id:null, quantity:float, estimated_unit_price:float, estimated_total_price:float, remarks:null}
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

    protected function canViewAllStores(): bool
    {
        return $this->hasInventoryWideAccess($this->purchaseOrderGlobalAccessPermissions());
    }
}
