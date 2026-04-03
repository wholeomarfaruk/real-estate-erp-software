<?php

namespace App\Livewire\Admin\Inventory\StockConsumption;

use App\Enums\Inventory\StockConsumptionStatus;
use App\Enums\Inventory\StoreType;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\StockConsumption;
use App\Models\Store;
use App\Services\Inventory\StockConsumptionService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class StockConsumptionForm extends Component
{
    use InteractsWithInventoryAccess;

    public ?StockConsumption $stockConsumptionRecord = null;

    public ?int $stockConsumptionId = null;

    public bool $editMode = false;

    public string $consumption_no = '';

    public string $consumption_date = '';

    public ?int $store_id = null;

    public ?int $project_id = null;

    public ?string $remarks = null;

    public string $status = 'draft';

    /**
     * @var array<int, array{product_id:int|string|null, quantity:float|int|string, unit_price:float|int|string, total_price:float|int|string, remarks:?string}>
     */
    public array $items = [];

    public function mount(?StockConsumption $stockConsumption = null): void
    {
        if ($stockConsumption && $stockConsumption->exists) {
            $this->authorizePermission('inventory.stock.consumption.update');

            $this->editMode = true;
            $this->stockConsumptionRecord = $stockConsumption->load('items', 'store');
            $this->stockConsumptionId = $stockConsumption->id;
            $this->consumption_no = $stockConsumption->consumption_no;
            $this->consumption_date = optional($stockConsumption->consumption_date)->format('Y-m-d') ?: now()->toDateString();
            $this->store_id = $stockConsumption->store_id;
            $this->project_id = $stockConsumption->project_id ?: $stockConsumption->store?->project_id;
            $this->remarks = $stockConsumption->remarks;
            $this->status = $stockConsumption->status?->value ?? StockConsumptionStatus::DRAFT->value;

            $this->ensureStoreAccessible((int) $stockConsumption->store_id);

            $this->items = $stockConsumption->items
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

        $this->authorizePermission('inventory.stock.consumption.create');
        $this->consumption_no = $this->generateConsumptionNo();
        $this->consumption_date = now()->toDateString();
        $this->items[] = $this->blankItem();
    }

    public function updatedStoreId($storeId): void
    {
        if (! $storeId) {
            $this->project_id = null;

            return;
        }

        $store = Store::query()->find($storeId);
        if (! $store) {
            return;
        }

        if (! $this->canViewAllStores()) {
            $this->ensureStoreAccessible((int) $storeId);
        }

        $this->project_id = $store->type === StoreType::PROJECT ? $store->project_id : null;
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
        $this->save(StockConsumptionStatus::DRAFT);

        return redirect()->route('admin.inventory.stock-consumptions.index');
    }

    public function postNow()
    {
        $this->authorizePermission('inventory.stock.consumption.post');

        $saved = $this->save(StockConsumptionStatus::DRAFT);

        try {
            app(StockConsumptionService::class)->postConsumption($saved, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock consumption posted successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);

            return;
        }

        return redirect()->route('admin.inventory.stock-consumptions.index');
    }

    public function render(): View
    {
        $storesQuery = Store::query()->active()->orderBy('name');

        if ($this->isProjectStoreManager()) {
            $storesQuery->project();
        }

        if (! $this->canViewAllStores()) {
            $storeIds = $this->getAccessibleStoreIds();
            $storesQuery->whereIn('id', $storeIds === [] ? [0] : $storeIds);
        }

        return view('livewire.admin.inventory.stock-consumption.stock-consumption-form', [
            'stores' => $storesQuery->get(['id', 'name', 'code', 'type', 'project_id']),
            'projects' => \App\Models\Project::query()->orderBy('name')->get(['id', 'name', 'code']),
            'products' => \App\Models\Product::query()->active()->orderBy('name')->get(['id', 'name', 'sku']),
            'statuses' => StockConsumptionStatus::cases(),
        ])->layout('layouts.admin.admin');
    }

    protected function save(StockConsumptionStatus $status): StockConsumption
    {
        if ($this->editMode) {
            $this->authorizePermission('inventory.stock.consumption.update');
        } else {
            $this->authorizePermission('inventory.stock.consumption.create');
        }

        if ($this->store_id) {
            $this->ensureStoreAccessible((int) $this->store_id);
        }

        $this->normalizeItems();

        $validated = $this->validate($this->rules(), $this->messages());

        $stockConsumption = DB::transaction(function () use ($validated, $status): StockConsumption {
            $store = Store::query()->findOrFail($validated['store_id']);

            if ($this->isProjectStoreManager() && $store->type !== StoreType::PROJECT) {
                throw new \DomainException('Project store manager can consume only from project store.');
            }

            $header = [
                'consumption_no' => $validated['consumption_no'],
                'consumption_date' => $validated['consumption_date'],
                'store_id' => $validated['store_id'],
                'project_id' => $store->project_id ?: $validated['project_id'],
                'remarks' => $validated['remarks'],
                'status' => $status->value,
                'created_by' => $this->editMode && $this->stockConsumptionRecord
                    ? $this->stockConsumptionRecord->created_by
                    : auth()->id(),
            ];

            $record = $this->stockConsumptionRecord;

            if ($this->editMode && $record) {
                if (in_array($record->status, [StockConsumptionStatus::POSTED, StockConsumptionStatus::CANCELLED], true)) {
                    throw new \DomainException('Posted or cancelled consumption cannot be edited.');
                }

                $record->update($header);
                $record->items()->delete();
            } else {
                $record = StockConsumption::query()->create($header);
                $this->stockConsumptionRecord = $record;
                $this->stockConsumptionId = $record->id;
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

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock consumption saved successfully.']);

        return $stockConsumption;
    }

    protected function rules(): array
    {
        return [
            'consumption_no' => ['required', 'string', 'max:100', Rule::unique('stock_consumptions', 'consumption_no')->ignore($this->stockConsumptionId)],
            'consumption_date' => ['required', 'date'],
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
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
            'items.*.product_id.required' => 'Please select a product for each row.',
            'store_id.required' => 'Please select a store.',
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

    protected function generateConsumptionNo(): string
    {
        $prefix = 'CON-'.now()->format('Ymd');
        $lastId = (int) StockConsumption::query()->max('id');

        return $prefix.'-'.str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
    }

    protected function isProjectStoreManager(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('store manager') || $user->hasRole('store_manager');
    }
}
