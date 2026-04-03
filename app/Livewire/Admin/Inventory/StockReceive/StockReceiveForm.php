<?php

namespace App\Livewire\Admin\Inventory\StockReceive;

use App\Enums\Inventory\StockReceiveStatus;
use App\Enums\Inventory\StoreType;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\StockReceive;
use App\Models\Store;
use App\Services\Inventory\StockReceiveService;
use Illuminate\Contracts\View\View;
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

    public ?int $supplier_id = null;

    public ?string $supplier_voucher = null;

    public ?int $store_id = null;

    public ?string $remarks = null;

    public string $status = 'draft';

    public bool $isLocked = false;

    /**
     * @var array<int, array{product_id:int|string|null, quantity:float|int|string, unit_price:float|int|string, total_price:float|int|string, remarks:?string}>
     */
    public array $items = [];

    public function mount(?StockReceive $stockReceive = null): void
    {
        if ($stockReceive && $stockReceive->exists) {
            $this->authorizePermission('inventory.stock.receive.update');

            $this->editMode = true;
            $this->stockReceiveRecord = $stockReceive->load('items');
            $this->stockReceiveId = $stockReceive->id;

            $this->receive_no = $stockReceive->receive_no;
            $this->receive_date = optional($stockReceive->receive_date)->format('Y-m-d') ?: now()->toDateString();
            $this->supplier_id = $stockReceive->supplier_id;
            $this->supplier_voucher = $stockReceive->supplier_voucher;
            $this->store_id = $stockReceive->store_id;
            $this->remarks = $stockReceive->remarks;
            $this->status = $stockReceive->status?->value ?? StockReceiveStatus::DRAFT->value;
            $this->isLocked = in_array($stockReceive->status, [StockReceiveStatus::POSTED, StockReceiveStatus::CANCELLED], true);

            $this->ensureStoreAccessible((int) $stockReceive->store_id);

            $this->items = $stockReceive->items
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

        $this->authorizePermission('inventory.stock.receive.create');

        $this->receive_no = app(StockReceiveService::class)->generateReceiveNo();
        $this->receive_date = now()->toDateString();
        $this->items[] = $this->blankItem();
    }

    public function addItem(): void
    {
        $this->items[] = $this->blankItem();
    }

    public function removeItem(int $index): void
    {
        if (count($this->items) <= 1) {
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

    public function saveDraft()
    {
        if ($this->isLocked) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Posted or cancelled receive cannot be edited.']);

            return;
        }

        try {
            $this->save(StockReceiveStatus::DRAFT);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);

            return;
        }

        return redirect()->route('admin.inventory.stock-receives.index');
    }

    public function postNow()
    {
        if ($this->isLocked) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Posted or cancelled receive cannot be edited.']);

            return;
        }

        $this->authorizePermission('inventory.stock.receive.post');

        try {
            $saved = $this->save(StockReceiveStatus::DRAFT);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);

            return;
        }

        try {
            app(StockReceiveService::class)->postReceive($saved);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock receive posted successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);

            return;
        }

        return redirect()->route('admin.inventory.stock-receives.index');
    }

    public function render(): View
    {
        if ($this->editMode) {
            $this->authorizePermission('inventory.stock.receive.update');
        } else {
            $this->authorizePermission('inventory.stock.receive.create');
        }

        $storesQuery = Store::query()->active()->office()->orderBy('name');
        $supplierQuery = \App\Models\Supplier::query()->active()->orderBy('name');

        if (! $this->canViewAllStores()) {
            $storeIds = $this->getAccessibleStoreIds();
            $storesQuery->whereIn('id', $storeIds === [] ? [0] : $storeIds);
        }

        return view('livewire.admin.inventory.stock-receive.stock-receive-form', [
            'stores' => $storesQuery->get(['id', 'name', 'code']),
            'suppliers' => $supplierQuery->get(['id', 'name', 'contact_person', 'phone']),
            'products' => \App\Models\Product::query()->active()->orderBy('name')->get(['id', 'name', 'sku']),
            'grandTotal' => $this->grandTotal,
            'isLocked' => $this->isLocked,
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

        $stockReceive = DB::transaction(function () use ($validated, $status): StockReceive {
            $header = [
                'receive_no' => $validated['receive_no'],
                'receive_date' => $validated['receive_date'],
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
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'supplier_voucher' => ['nullable', 'string', 'max:255'],
            'store_id' => [
                'required',
                'integer',
                Rule::exists('stores', 'id')->where(fn ($query) => $query->where('type', StoreType::OFFICE->value)),
            ],
            'remarks' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
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
            'items.*.product_id.required' => 'Please select a product for each row.',
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
        $unitPrice = (float) ($this->items[$index]['unit_price'] ?? 0);
        $this->items[$index]['total_price'] = round($quantity * $unitPrice, 2);
    }

    protected function normalizeItems(): void
    {
        foreach (array_keys($this->items) as $index) {
            $this->recalculateItem($index);
        }
    }

    protected function generateReceiveNo(): string
    {
        return app(StockReceiveService::class)->generateReceiveNo();
    }

    public function getGrandTotalProperty(): float
    {
        $total = collect($this->items)->sum(function (array $item): float {
            return (float) ($item['total_price'] ?? 0);
        });

        return round($total, 2);
    }
}
