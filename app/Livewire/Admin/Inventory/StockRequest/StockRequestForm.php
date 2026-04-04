<?php

namespace App\Livewire\Admin\Inventory\StockRequest;

use App\Enums\Inventory\StockRequestPriority;
use App\Enums\Inventory\StockRequestStatus;
use App\Enums\Inventory\StoreType;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\Product;
use App\Models\Project;
use App\Models\StockRequest;
use App\Models\Store;
use App\Services\Inventory\StockRequestService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class StockRequestForm extends Component
{
    use InteractsWithInventoryAccess;

    public ?StockRequest $stockRequestRecord = null;

    public ?int $stockRequestId = null;

    public bool $editMode = false;

    public bool $isLocked = false;

    public string $request_no = '';

    public string $request_date = '';

    public ?int $requester_store_id = null;

    public ?int $source_store_id = null;

    public ?int $project_id = null;

    public string $priority = 'normal';

    public ?string $remarks = null;

    public string $status = 'draft';

    /**
     * @var array<int, array{product_id:int|string|null, quantity:float|int|string, approved_quantity:float|int|string|null, fulfilled_quantity:float|int|string, remarks:?string}>
     */
    public array $items = [];

    public function mount(?StockRequest $stockRequest = null): void
    {
        if ($stockRequest && $stockRequest->exists) {
            $this->authorizePermission('inventory.stock_request.update');

            $this->editMode = true;
            $this->stockRequestRecord = $stockRequest->load('items');
            $this->stockRequestId = $stockRequest->id;
            $this->request_no = $stockRequest->request_no;
            $this->request_date = optional($stockRequest->request_date)->format('Y-m-d') ?: now()->toDateString();
            $this->requester_store_id = $stockRequest->requester_store_id;
            $this->source_store_id = $stockRequest->source_store_id;
            $this->project_id = $stockRequest->project_id;
            $this->priority = $stockRequest->priority?->value ?? StockRequestPriority::NORMAL->value;
            $this->remarks = $stockRequest->remarks;
            $this->status = $stockRequest->status?->value ?? StockRequestStatus::DRAFT->value;
            $this->isLocked = $stockRequest->status !== StockRequestStatus::DRAFT;

            $this->ensureStoreAccessible((int) $stockRequest->requester_store_id);

            $this->items = $stockRequest->items
                ->map(fn ($item): array => [
                    'product_id' => $item->product_id,
                    'quantity' => (float) $item->quantity,
                    'approved_quantity' => $item->approved_quantity !== null ? (float) $item->approved_quantity : null,
                    'fulfilled_quantity' => (float) $item->fulfilled_quantity,
                    'remarks' => $item->remarks,
                ])
                ->values()
                ->all();

            if ($this->items === []) {
                $this->items[] = $this->blankItem();
            }

            return;
        }

        $this->authorizePermission('inventory.stock_request.create');

        $this->request_no = app(StockRequestService::class)->generateRequestNo();
        $this->request_date = now()->toDateString();
        $this->priority = StockRequestPriority::NORMAL->value;
        $this->items[] = $this->blankItem();
    }

    public function updatedRequesterStoreId($storeId): void
    {
        if (! $storeId) {
            $this->project_id = null;

            return;
        }

        if (! $this->canViewAllStores()) {
            $this->ensureStoreAccessible((int) $storeId);
        }

        $store = Store::query()->find($storeId);

        if (! $store) {
            return;
        }

        if ($store->type === StoreType::PROJECT) {
            $this->project_id = $store->project_id ?: $this->project_id;
        }
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
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only draft stock request can be edited.']);

            return;
        }

        try {
            $this->save(StockRequestStatus::DRAFT);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);

            return;
        }

        return redirect()->route('admin.inventory.stock-requests.index');
    }

    public function submitNow()
    {
        if ($this->isLocked) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only draft stock request can be edited.']);

            return;
        }

        $this->authorizePermission('inventory.stock_request.submit');

        try {
            $saved = $this->save(StockRequestStatus::DRAFT);
            app(StockRequestService::class)->submitRequest($saved, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock request submitted successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);

            return;
        }

        return redirect()->route('admin.inventory.stock-requests.index');
    }

    public function render(): View
    {
        if ($this->editMode) {
            $this->authorizePermission('inventory.stock_request.update');
        } else {
            $this->authorizePermission('inventory.stock_request.create');
        }

        $requesterStoresQuery = Store::query()->active()->orderBy('name');
        if (! $this->canViewAllStores()) {
            $storeIds = $this->getAccessibleStoreIds();
            $requesterStoresQuery->whereIn('id', $storeIds === [] ? [0] : $storeIds);
        }

        return view('livewire.admin.inventory.stock-request.stock-request-form', [
            'requesterStores' => $requesterStoresQuery->get(['id', 'name', 'code', 'type', 'project_id']),
            'sourceStores' => Store::query()->active()->orderBy('name')->get(['id', 'name', 'code', 'type']),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name', 'code']),
            'products' => Product::query()->active()->orderBy('name')->get(['id', 'name', 'sku']),
            'priorities' => StockRequestPriority::cases(),
            'isLocked' => $this->isLocked,
        ])->layout('layouts.admin.admin');
    }

    protected function save(StockRequestStatus $status): StockRequest
    {
        if ($this->isLocked) {
            throw new \DomainException('Only draft stock request can be edited.');
        }

        if ($this->editMode) {
            $this->authorizePermission('inventory.stock_request.update');
        } else {
            $this->authorizePermission('inventory.stock_request.create');
        }

        if ($this->requester_store_id) {
            $this->ensureStoreAccessible((int) $this->requester_store_id);
        }

        $validated = $this->validate($this->rules(), $this->messages());

        $this->ensureStoreAccessible((int) $validated['requester_store_id']);

        $requesterStore = Store::query()->findOrFail((int) $validated['requester_store_id']);

        if ($requesterStore->type === StoreType::PROJECT && ! $validated['project_id']) {
            $validated['project_id'] = $requesterStore->project_id;
        }

        $stockRequest = DB::transaction(function () use ($validated, $status): StockRequest {
            $header = [
                'request_no' => $validated['request_no'],
                'request_date' => $validated['request_date'],
                'requester_store_id' => $validated['requester_store_id'],
                'source_store_id' => $validated['source_store_id'],
                'project_id' => $validated['project_id'],
                'priority' => $validated['priority'],
                'remarks' => $validated['remarks'],
                'status' => $status->value,
                'requested_by' => $this->editMode && $this->stockRequestRecord
                    ? $this->stockRequestRecord->requested_by
                    : auth()->id(),
            ];

            $record = $this->stockRequestRecord;

            if ($this->editMode && $record) {
                if ($record->status !== StockRequestStatus::DRAFT) {
                    throw new \DomainException('Only draft stock request can be edited.');
                }

                $record->update($header);
                $record->items()->delete();
            } else {
                $record = StockRequest::query()->create($header);
                $this->stockRequestRecord = $record;
                $this->stockRequestId = $record->id;
                $this->editMode = true;
            }

            foreach ($validated['items'] as $item) {
                $record->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'approved_quantity' => null,
                    'fulfilled_quantity' => 0,
                    'remarks' => $item['remarks'] ?? null,
                ]);
            }

            return $record->refresh();
        });

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock request saved successfully.']);

        return $stockRequest;
    }

    protected function rules(): array
    {
        return [
            'request_no' => ['required', 'string', 'max:100', Rule::unique('stock_requests', 'request_no')->ignore($this->stockRequestId)],
            'request_date' => ['required', 'date'],
            'requester_store_id' => ['required', 'integer', 'exists:stores,id'],
            'source_store_id' => ['nullable', 'integer', 'different:requester_store_id', 'exists:stores,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'priority' => ['required', Rule::in(array_map(fn (StockRequestPriority $priority): string => $priority->value, StockRequestPriority::cases()))],
            'remarks' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id', 'distinct'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.remarks' => ['nullable', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'source_store_id.different' => 'Source store must be different from requester store.',
            'items.*.product_id.required' => 'Please select a product for each row.',
            'items.*.product_id.distinct' => 'Same product cannot be added multiple times.',
            'items.*.quantity.min' => 'Requested quantity must be at least 0.001.',
        ];
    }

    /**
     * @return array{product_id:null, quantity:float, approved_quantity:null, fulfilled_quantity:float, remarks:null}
     */
    protected function blankItem(): array
    {
        return [
            'product_id' => null,
            'quantity' => 1,
            'approved_quantity' => null,
            'fulfilled_quantity' => 0,
            'remarks' => null,
        ];
    }
}
