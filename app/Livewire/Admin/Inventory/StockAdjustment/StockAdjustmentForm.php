<?php

namespace App\Livewire\Admin\Inventory\StockAdjustment;

use App\Enums\Inventory\StockAdjustmentStatus;
use App\Enums\Inventory\StockAdjustmentType;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\Store;
use App\Services\Inventory\StockAdjustmentService;
use App\Services\Inventory\StockService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class StockAdjustmentForm extends Component
{
    use InteractsWithInventoryAccess;

    public ?StockAdjustment $stockAdjustmentRecord = null;

    public ?int $stockAdjustmentId = null;

    public bool $editMode = false;

    public bool $isLocked = false;

    public string $adjustment_no = '';

    public string $adjustment_date = '';

    public ?int $store_id = null;

    public string $adjustment_type = 'in';

    public ?string $reason = null;

    public ?string $remarks = null;

    public string $status = 'draft';

    /**
     * @var array<int, array{product_id:int|string|null, quantity:float|int|string, unit_price:float|int|string|null, total_price:float|int|string|null, remarks:?string}>
     */
    public array $items = [];

    public function mount(?StockAdjustment $stockAdjustment = null): void
    {
        if ($stockAdjustment && $stockAdjustment->exists) {
            $this->authorizePermission('inventory.stock.adjustment.update');

            $this->editMode = true;
            $this->stockAdjustmentRecord = $stockAdjustment->load('items');
            $this->stockAdjustmentId = $stockAdjustment->id;
            $this->adjustment_no = $stockAdjustment->adjustment_no;
            $this->adjustment_date = optional($stockAdjustment->adjustment_date)->format('Y-m-d') ?: now()->toDateString();
            $this->store_id = $stockAdjustment->store_id;
            $this->adjustment_type = $stockAdjustment->adjustment_type?->value ?? StockAdjustmentType::IN->value;
            $this->reason = $stockAdjustment->reason;
            $this->remarks = $stockAdjustment->remarks;
            $this->status = $stockAdjustment->status?->value ?? StockAdjustmentStatus::DRAFT->value;
            $this->isLocked = in_array($stockAdjustment->status, [StockAdjustmentStatus::POSTED, StockAdjustmentStatus::CANCELLED], true);

            $this->ensureStoreAccessible((int) $stockAdjustment->store_id);

            $this->items = $stockAdjustment->items
                ->map(fn ($item): array => [
                    'product_id' => $item->product_id,
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

            return;
        }

        $this->authorizePermission('inventory.stock.adjustment.create');

        $this->adjustment_no = $this->generateAdjustmentNo();
        $this->adjustment_date = now()->toDateString();
        $this->adjustment_type = StockAdjustmentType::IN->value;
        $this->items[] = $this->blankItem();
    }

    public function updatedStoreId($storeId): void
    {
        if (! $storeId || $this->canViewAllStores()) {
            return;
        }

        $this->ensureStoreAccessible((int) $storeId);
    }

    public function updatedAdjustmentType(string $type): void
    {
        if ($type === StockAdjustmentType::OUT->value) {
            foreach (array_keys($this->items) as $index) {
                $this->items[$index]['unit_price'] = 0;
                $this->items[$index]['total_price'] = 0;
            }

            return;
        }

        foreach (array_keys($this->items) as $index) {
            $this->recalculateItem($index);
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

    public function availableQuantityFor(int $index): float
    {
        if (! $this->store_id || ! isset($this->items[$index]['product_id'])) {
            return 0;
        }

        $productId = (int) ($this->items[$index]['product_id'] ?? 0);
        if ($productId <= 0) {
            return 0;
        }

        return app(StockService::class)->getAvailableQty((int) $this->store_id, $productId);
    }

    public function saveDraft()
    {
        if ($this->isLocked) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Posted or cancelled adjustment cannot be edited.']);

            return;
        }

        try {
            $this->save(StockAdjustmentStatus::DRAFT);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);

            return;
        }

        return redirect()->route('admin.inventory.stock-adjustments.index');
    }

    public function postNow()
    {
        if ($this->isLocked) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Posted or cancelled adjustment cannot be edited.']);

            return;
        }

        $this->authorizePermission('inventory.stock.adjustment.post');

        try {
            $saved = $this->save(StockAdjustmentStatus::DRAFT);
            app(StockAdjustmentService::class)->postAdjustment($saved, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock adjustment posted successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);

            return;
        }

        return redirect()->route('admin.inventory.stock-adjustments.index');
    }

    public function render(): View
    {
        if ($this->editMode) {
            $this->authorizePermission('inventory.stock.adjustment.update');
        } else {
            $this->authorizePermission('inventory.stock.adjustment.create');
        }

        $storesQuery = Store::query()->active()->orderBy('name');
        if (! $this->canViewAllStores()) {
            $storeIds = $this->getAccessibleStoreIds();
            $storesQuery->whereIn('id', $storeIds === [] ? [0] : $storeIds);
        }

        return view('livewire.admin.inventory.stock-adjustment.stock-adjustment-form', [
            'stores' => $storesQuery->get(['id', 'name', 'code', 'type']),
            'products' => Product::query()->active()->orderBy('name')->get(['id', 'name', 'sku']),
            'isLocked' => $this->isLocked,
            'isAdjustmentOut' => $this->adjustment_type === StockAdjustmentType::OUT->value,
            'grandTotal' => $this->grandTotal,
        ])->layout('layouts.admin.admin');
    }

    protected function save(StockAdjustmentStatus $status): StockAdjustment
    {
        if ($this->isLocked) {
            throw new \DomainException('Posted or cancelled adjustment cannot be edited.');
        }

        if ($this->editMode) {
            $this->authorizePermission('inventory.stock.adjustment.update');
        } else {
            $this->authorizePermission('inventory.stock.adjustment.create');
        }

        if ($this->store_id) {
            $this->ensureStoreAccessible((int) $this->store_id);
        }

        $this->normalizeItems();

        $validated = $this->validate($this->rules(), $this->messages());

        $this->ensureStoreAccessible((int) $validated['store_id']);

        if ($validated['adjustment_type'] === StockAdjustmentType::OUT->value) {
            $this->validateStockAvailabilityForOut((int) $validated['store_id'], $validated['items']);
        }

        $stockAdjustment = DB::transaction(function () use ($validated, $status): StockAdjustment {
            $header = [
                'adjustment_no' => $validated['adjustment_no'],
                'adjustment_date' => $validated['adjustment_date'],
                'store_id' => $validated['store_id'],
                'adjustment_type' => $validated['adjustment_type'],
                'reason' => $validated['reason'],
                'remarks' => $validated['remarks'],
                'status' => $status->value,
                'created_by' => $this->editMode && $this->stockAdjustmentRecord
                    ? $this->stockAdjustmentRecord->created_by
                    : auth()->id(),
            ];

            $record = $this->stockAdjustmentRecord;

            if ($this->editMode && $record) {
                if (in_array($record->status, [StockAdjustmentStatus::POSTED, StockAdjustmentStatus::CANCELLED], true)) {
                    throw new \DomainException('Posted or cancelled adjustment cannot be edited.');
                }

                $record->update($header);
                $record->items()->delete();
            } else {
                $record = StockAdjustment::query()->create($header);
                $this->stockAdjustmentRecord = $record;
                $this->stockAdjustmentId = $record->id;
                $this->editMode = true;
            }

            $isAdjustmentOut = $validated['adjustment_type'] === StockAdjustmentType::OUT->value;

            foreach ($validated['items'] as $item) {
                $quantity = (float) $item['quantity'];
                $unitPrice = $isAdjustmentOut ? 0.0 : (float) ($item['unit_price'] ?? 0);
                $totalPrice = round($quantity * $unitPrice, 2);

                $record->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'remarks' => $item['remarks'] ?? null,
                ]);
            }

            return $record->refresh();
        });

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock adjustment saved successfully.']);

        return $stockAdjustment;
    }

    protected function rules(): array
    {
        return [
            'adjustment_no' => ['required', 'string', 'max:100', Rule::unique('stock_adjustments', 'adjustment_no')->ignore($this->stockAdjustmentId)],
            'adjustment_date' => ['required', 'date'],
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'adjustment_type' => ['required', 'string', Rule::in(array_map(fn (StockAdjustmentType $type): string => $type->value, StockAdjustmentType::cases()))],
            'reason' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.total_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.remarks' => ['nullable', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'items.*.product_id.required' => 'Please select a product for each row.',
            'store_id.required' => 'Please select a store.',
            'adjustment_type.in' => 'Selected adjustment type is invalid.',
        ];
    }

    /**
     * @return array{product_id:null, quantity:float, unit_price:float, total_price:float, remarks:null}
     */
    protected function blankItem(): array
    {
        return [
            'product_id' => null,
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

        if ($this->adjustment_type === StockAdjustmentType::OUT->value) {
            $this->items[$index]['unit_price'] = 0;
            $this->items[$index]['total_price'] = 0;

            return;
        }

        $unitPrice = (float) ($this->items[$index]['unit_price'] ?? 0);
        $this->items[$index]['total_price'] = round($quantity * $unitPrice, 2);
    }

    protected function normalizeItems(): void
    {
        foreach (array_keys($this->items) as $index) {
            $this->recalculateItem($index);
        }
    }

    protected function validateStockAvailabilityForOut(int $storeId, array $items): void
    {
        $requiredByProduct = [];

        foreach ($items as $item) {
            $quantity = (float) ($item['quantity'] ?? 0);
            $productId = (int) ($item['product_id'] ?? 0);
            $requiredByProduct[$productId] = ($requiredByProduct[$productId] ?? 0) + $quantity;
        }

        foreach ($requiredByProduct as $productId => $requiredQty) {
            $availableQty = app(StockService::class)->getAvailableQty($storeId, $productId);

            if ($availableQty < $requiredQty) {
                $productName = Product::query()->whereKey($productId)->value('name') ?? 'Selected product';
                throw new \DomainException(
                    $productName.' has insufficient stock for adjustment out. Available: '
                    .number_format($availableQty, 3).', Required: '.number_format($requiredQty, 3).'.'
                );
            }
        }
    }

    protected function generateAdjustmentNo(): string
    {
        return app(StockAdjustmentService::class)->generateAdjustmentNo();
    }

    public function getGrandTotalProperty(): float
    {
        $total = collect($this->items)->sum(fn (array $item): float => (float) ($item['total_price'] ?? 0));

        return round($total, 2);
    }
}
