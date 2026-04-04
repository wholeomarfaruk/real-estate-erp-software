<?php

namespace App\Livewire\Admin\Inventory\StockTransfer;

use App\Enums\Inventory\TransferStatus;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\Product;
use App\Models\StockRequest;
use App\Models\Store;
use App\Models\TransferTransaction;
use App\Services\Inventory\StockService;
use App\Services\Inventory\StockTransferService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class StockTransferForm extends Component
{
    use InteractsWithInventoryAccess;

    public ?TransferTransaction $transferRecord = null;

    public ?int $transferId = null;

    public ?int $stock_request_id = null;

    public bool $editMode = false;

    public bool $isLocked = false;

    public string $transfer_no = '';

    public ?int $sender_store_id = null;

    public ?int $receiver_store_id = null;

    public string $transfer_date = '';

    public ?string $remarks = null;

    public string $status = 'draft';

    /**
     * @var array<int, array{product_id:int|string|null, quantity:float|int|string, received_quantity:float|int|string|null, unit_price:float|int|string, total_price:float|int|string, remarks:?string}>
     */
    public array $items = [];

    public function mount(?TransferTransaction $transferTransaction = null): void
    {
        if ($transferTransaction && $transferTransaction->exists) {
            $this->authorizePermission('inventory.stock.transfer.update');

            $this->editMode = true;
            $this->transferRecord = $transferTransaction->load('items');
            $this->transferId = $transferTransaction->id;
            $this->transfer_no = $transferTransaction->transfer_no;
            $this->sender_store_id = $transferTransaction->sender_store_id;
            $this->receiver_store_id = $transferTransaction->receiver_store_id;
            $this->transfer_date = optional($transferTransaction->transfer_date)->format('Y-m-d') ?: now()->toDateString();
            $this->remarks = $transferTransaction->remarks;
            $this->status = $transferTransaction->status?->value ?? TransferStatus::DRAFT->value;
            $this->isLocked = $transferTransaction->status !== TransferStatus::DRAFT;

            $this->ensureTransferAccessible($transferTransaction);

            $this->items = $transferTransaction->items
                ->map(fn ($item): array => [
                    'product_id' => $item->product_id,
                    'quantity' => (float) $item->quantity,
                    'received_quantity' => $item->received_quantity ? (float) $item->received_quantity : null,
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

        $this->authorizePermission('inventory.stock.transfer.create');

        $this->transfer_no = app(StockTransferService::class)->generateTransferNo();
        $this->transfer_date = now()->toDateString();
        $this->items[] = $this->blankItem();

        $this->prefillFromStockRequest();
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
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only draft transfer can be edited.']);

            return;
        }

        try {
            $this->save(TransferStatus::DRAFT);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);

            return;
        }

        return redirect()->route('admin.inventory.stock-transfers.index');
    }

    public function requestNow()
    {
        if ($this->isLocked) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only draft transfer can be edited.']);

            return;
        }

        $this->authorizePermission('inventory.stock.transfer.request');

        try {
            $saved = $this->save(TransferStatus::DRAFT);
            app(StockTransferService::class)->requestTransfer($saved, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Transfer requested successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);

            return;
        }

        return redirect()->route('admin.inventory.stock-transfers.index');
    }

    public function render(): View
    {
        if ($this->editMode) {
            $this->authorizePermission('inventory.stock.transfer.update');
        } else {
            $this->authorizePermission('inventory.stock.transfer.create');
        }

        $storesQuery = Store::query()->active()->orderBy('name');

        if (! $this->canViewAllStores()) {
            $storeIds = $this->getAccessibleStoreIds();
            $storesQuery->whereIn('id', $storeIds === [] ? [0] : $storeIds);
        }

        return view('livewire.admin.inventory.stock-transfer.stock-transfer-form', [
            'stores' => $storesQuery->get(['id', 'name', 'code', 'type']),
            'products' => Product::query()->active()->orderBy('name')->get(['id', 'name', 'sku']),
            'isLocked' => $this->isLocked,
        ])->layout('layouts.admin.admin');
    }

    public function availableQuantityFor(int $index): float
    {
        if (! $this->sender_store_id || ! isset($this->items[$index]['product_id'])) {
            return 0;
        }

        $productId = (int) ($this->items[$index]['product_id'] ?? 0);
        if ($productId <= 0) {
            return 0;
        }

        return app(StockService::class)->getAvailableQty((int) $this->sender_store_id, $productId);
    }

    protected function save(TransferStatus $status): TransferTransaction
    {
        if ($this->isLocked) {
            throw new \DomainException('Only draft transfer can be edited.');
        }

        if ($this->editMode) {
            $this->authorizePermission('inventory.stock.transfer.update');
        } else {
            $this->authorizePermission('inventory.stock.transfer.create');
        }

        $this->normalizeItems();

        $validated = $this->validate($this->rules(), $this->messages());

        $this->ensureStoreAccessible((int) $validated['sender_store_id']);

        $this->validateStockAvailability(
            senderStoreId: (int) $validated['sender_store_id'],
            items: $validated['items']
        );

        $transfer = DB::transaction(function () use ($validated, $status): TransferTransaction {
            $header = [
                'transfer_no' => $validated['transfer_no'],
                'sender_store_id' => $validated['sender_store_id'],
                'receiver_store_id' => $validated['receiver_store_id'],
                'transfer_date' => $validated['transfer_date'],
                'remarks' => $validated['remarks'],
                'status' => $status->value,
            ];

            $record = $this->transferRecord;

            if ($this->editMode && $record) {
                if ($record->status !== TransferStatus::DRAFT) {
                    throw new \DomainException('Only draft transfer can be edited.');
                }

                $record->update($header);
                $record->items()->delete();
            } else {
                $record = TransferTransaction::query()->create($header);
                $this->transferRecord = $record;
                $this->transferId = $record->id;
                $this->editMode = true;
            }

            foreach ($validated['items'] as $item) {
                $record->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'received_quantity' => null,
                    'unit_price' => 0,
                    'total_price' => 0,
                    'remarks' => $item['remarks'] ?? null,
                ]);
            }

            return $record->refresh();
        });

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Transfer saved successfully.']);

        return $transfer;
    }

    protected function rules(): array
    {
        return [
            'transfer_no' => ['required', 'string', 'max:100', Rule::unique('transfer_transactions', 'transfer_no')->ignore($this->transferId)],
            'transfer_date' => ['required', 'date'],
            'sender_store_id' => ['required', 'integer', 'exists:stores,id'],
            'receiver_store_id' => ['required', 'integer', 'different:sender_store_id', 'exists:stores,id'],
            'remarks' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.remarks' => ['nullable', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'receiver_store_id.different' => 'Sender and receiver stores must be different.',
            'items.*.product_id.required' => 'Please select a product for each row.',
        ];
    }

    /**
     * @return array{product_id:null, quantity:float, received_quantity:null, unit_price:float, total_price:float, remarks:null}
     */
    protected function blankItem(): array
    {
        return [
            'product_id' => null,
            'quantity' => 1,
            'received_quantity' => null,
            'unit_price' => 0,
            'total_price' => 0,
            'remarks' => null,
        ];
    }

    protected function normalizeItems(): void
    {
        foreach (array_keys($this->items) as $index) {
            $quantity = (float) ($this->items[$index]['quantity'] ?? 0);

            $this->items[$index]['quantity'] = $quantity;
            $this->items[$index]['received_quantity'] = null;
            $this->items[$index]['unit_price'] = 0;
            $this->items[$index]['total_price'] = 0;
        }
    }

    protected function validateStockAvailability(int $senderStoreId, array $items): void
    {
        $requiredByProduct = [];

        foreach ($items as $item) {
            $quantity = (float) ($item['quantity'] ?? 0);
            $productId = (int) ($item['product_id'] ?? 0);
            $requiredByProduct[$productId] = ($requiredByProduct[$productId] ?? 0) + $quantity;
        }

        foreach ($requiredByProduct as $productId => $requiredQty) {
            $availableQty = app(StockService::class)->getAvailableQty($senderStoreId, (int) $productId);

            if ($availableQty < $requiredQty) {
                $productName = Product::query()->whereKey($productId)->value('name') ?? 'Selected product';
                throw new \DomainException(
                    $productName.' has insufficient stock in sender store. Available: '
                    .number_format($availableQty, 3).', Required: '.number_format($requiredQty, 3).'.'
                );
            }
        }
    }

    protected function ensureTransferAccessible(TransferTransaction $transfer): void
    {
        if ($this->canViewAllStores()) {
            return;
        }

        $storeIds = $this->getAccessibleStoreIds();

        abort_unless(
            in_array((int) $transfer->sender_store_id, $storeIds, true)
            || in_array((int) $transfer->receiver_store_id, $storeIds, true),
            403,
            'You are not allowed to access this transfer.'
        );
    }

    protected function prefillFromStockRequest(): void
    {
        $stockRequestId = (int) request()->integer('stock_request_id');
        if ($stockRequestId <= 0) {
            return;
        }

        $stockRequest = StockRequest::query()
            ->with('items')
            ->find($stockRequestId);

        if (! $stockRequest || ! in_array($stockRequest->status?->value, ['approved', 'partially_fulfilled'], true)) {
            return;
        }

        if (! $this->canViewAllStores()) {
            $storeIds = $this->getAccessibleStoreIds();

            if (! in_array((int) $stockRequest->requester_store_id, $storeIds, true)
                && ! ($stockRequest->source_store_id && in_array((int) $stockRequest->source_store_id, $storeIds, true))) {
                return;
            }
        }

        $this->stock_request_id = (int) $stockRequest->id;
        $this->sender_store_id = $stockRequest->source_store_id ? (int) $stockRequest->source_store_id : null;
        $this->receiver_store_id = (int) $stockRequest->requester_store_id;
        $this->remarks = trim(($this->remarks ? $this->remarks.' ' : '').'Prefilled from stock request '.$stockRequest->request_no.'.');

        $prefilledItems = [];
        foreach ($stockRequest->items as $item) {
            $targetQty = (float) ($item->approved_quantity ?? $item->quantity);
            $remainingQty = round(max(0, $targetQty - (float) $item->fulfilled_quantity), 3);

            if ($remainingQty <= 0) {
                continue;
            }

            $prefilledItems[] = [
                'product_id' => (int) $item->product_id,
                'quantity' => $remainingQty,
                'received_quantity' => null,
                'unit_price' => 0,
                'total_price' => 0,
                'remarks' => $item->remarks,
            ];
        }

        if ($prefilledItems !== []) {
            $this->items = $prefilledItems;
        }
    }
}
