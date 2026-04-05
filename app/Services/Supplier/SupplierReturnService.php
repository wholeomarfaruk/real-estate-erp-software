<?php

namespace App\Services\Supplier;

use App\Enums\Supplier\SupplierReturnReferenceType;
use App\Enums\Supplier\SupplierReturnStatus;
use App\Models\PurchaseOrder;
use App\Models\StockReceive;
use App\Models\SupplierBill;
use App\Models\SupplierReturn;
use App\Models\SupplierReturnItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SupplierReturnService
{
    public function generateReturnNo(): string
    {
        $lastId = (int) SupplierReturn::query()->withTrashed()->max('id');

        return 'SRT-'.str_pad((string) ($lastId + 1), 6, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, array{product_id:int|string|null,description:?string,qty:float|int|string,unit_id:int|string|null,rate:float|int|string,line_total:float|int|string}>  $items
     */
    public function saveReturn(array $payload, array $items, ?SupplierReturn $supplierReturn = null, ?int $actorId = null): SupplierReturn
    {
        $resolvedActorId = $this->resolveActorId($actorId);

        return DB::transaction(function () use ($payload, $items, $supplierReturn, $resolvedActorId): SupplierReturn {
            $record = null;

            if ($supplierReturn) {
                $record = SupplierReturn::query()
                    ->with('items')
                    ->lockForUpdate()
                    ->findOrFail($supplierReturn->id);

                if (! $record->canEdit()) {
                    throw new \DomainException('Only draft supplier returns are editable.');
                }
            }

            $referenceContext = $this->resolveReferenceContext($payload, $record?->id);
            $supplierId = (int) ($payload['supplier_id'] ?? 0);

            if ($supplierId <= 0) {
                throw new \DomainException('Supplier is required.');
            }

            if ($referenceContext['supplier_id'] !== null && (int) $referenceContext['supplier_id'] !== $supplierId) {
                throw new \DomainException('Selected supplier does not match linked reference supplier.');
            }

            $normalizedItems = $this->normalizeItems($items);

            if ($normalizedItems === []) {
                throw new \DomainException('At least one return item is required.');
            }

            $this->validateSourceQuantities($normalizedItems, $referenceContext['available_qty_map']);

            $subtotal = round(collect($normalizedItems)->sum('line_total'), 2);
            $totalAmount = $subtotal;

            if ($totalAmount <= 0) {
                throw new \DomainException('Return total amount must be greater than zero.');
            }

            $record ??= new SupplierReturn();
            $record->fill([
                'supplier_id' => $supplierId,
                'return_no' => (string) ($payload['return_no'] ?? ''),
                'return_date' => $payload['return_date'] ?? now()->toDateString(),
                'reference_type' => $referenceContext['reference_type']->value,
                'reference_id' => $referenceContext['reference_id'],
                'supplier_bill_id' => $referenceContext['supplier_bill_id'],
                'stock_receive_id' => $referenceContext['stock_receive_id'],
                'purchase_order_id' => $referenceContext['purchase_order_id'],
                'reason' => isset($payload['reason']) ? trim((string) $payload['reason']) : null,
                'notes' => isset($payload['notes']) ? trim((string) $payload['notes']) : null,
                'subtotal' => $subtotal,
                'total_amount' => $totalAmount,
                'status' => SupplierReturnStatus::DRAFT->value,
                'updated_by' => $resolvedActorId,
            ]);

            if (! $record->exists) {
                $record->created_by = $resolvedActorId;
            }

            $record->save();
            $record->items()->delete();

            foreach ($normalizedItems as $item) {
                $record->items()->create([
                    'product_id' => $item['product_id'],
                    'description' => $item['description'],
                    'qty' => $item['qty'],
                    'unit_id' => $item['unit_id'],
                    'rate' => $item['rate'],
                    'line_total' => $item['line_total'],
                ]);
            }

            app(SupplierLedgerService::class)->postReturn($record, $resolvedActorId, false);

            return $record->refresh();
        });
    }

    public function approveReturn(SupplierReturn $supplierReturn, ?int $actorId = null): SupplierReturn
    {
        $resolvedActorId = $this->resolveActorId($actorId);

        return DB::transaction(function () use ($supplierReturn, $resolvedActorId): SupplierReturn {
            $record = SupplierReturn::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($supplierReturn->id);

            if (! $record->canApprove()) {
                throw new \DomainException('Only draft supplier returns can be approved.');
            }

            $referenceContext = $this->resolveReferenceContext([
                'reference_type' => $record->reference_type?->value ?? SupplierReturnReferenceType::MANUAL->value,
                'supplier_id' => (int) $record->supplier_id,
                'supplier_bill_id' => $record->supplier_bill_id,
                'stock_receive_id' => $record->stock_receive_id,
                'purchase_order_id' => $record->purchase_order_id,
                'reference_id' => $record->reference_id,
            ], (int) $record->id);

            $normalizedItems = $this->normalizeItems(
                $record->items
                    ->map(fn (SupplierReturnItem $item): array => [
                        'product_id' => $item->product_id,
                        'description' => $item->description,
                        'qty' => (float) $item->qty,
                        'unit_id' => $item->unit_id,
                        'rate' => (float) $item->rate,
                        'line_total' => (float) $item->line_total,
                    ])
                    ->all()
            );

            if ($normalizedItems === []) {
                throw new \DomainException('Supplier return has no valid item rows.');
            }

            $totalAmount = round(collect($normalizedItems)->sum('line_total'), 2);

            if ($totalAmount <= 0) {
                throw new \DomainException('Return total amount must be greater than zero.');
            }

            $this->validateSourceQuantities($normalizedItems, $referenceContext['available_qty_map']);

            $record->update([
                'status' => SupplierReturnStatus::APPROVED->value,
                'approved_by' => $resolvedActorId,
                'approved_at' => now(),
                'updated_by' => $resolvedActorId,
            ]);

            app(SupplierLedgerService::class)->postReturn($record, $resolvedActorId, false);

            return $record->refresh();
        });
    }

    public function cancelReturn(SupplierReturn $supplierReturn, ?int $actorId = null): SupplierReturn
    {
        $resolvedActorId = $this->resolveActorId($actorId);

        return DB::transaction(function () use ($supplierReturn, $resolvedActorId): SupplierReturn {
            $record = SupplierReturn::query()
                ->lockForUpdate()
                ->findOrFail($supplierReturn->id);

            if (! $record->canCancel()) {
                throw new \DomainException('This supplier return is already cancelled.');
            }

            $record->update([
                'status' => SupplierReturnStatus::CANCELLED->value,
                'updated_by' => $resolvedActorId,
            ]);

            app(SupplierLedgerService::class)->postReturn($record, $resolvedActorId, false);

            return $record->refresh();
        });
    }

    /**
     * @param  array<int, array{product_id:int|string|null,description:?string,qty:float|int|string,unit_id:int|string|null,rate:float|int|string,line_total:float|int|string}>  $items
     * @return array<int, array{product_id:?int,description:?string,qty:float,unit_id:?int,rate:float,line_total:float}>
     */
    protected function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $productId = isset($item['product_id']) && $item['product_id'] !== '' ? (int) $item['product_id'] : null;
            $description = isset($item['description']) ? trim((string) $item['description']) : null;
            $unitId = isset($item['unit_id']) && $item['unit_id'] !== '' ? (int) $item['unit_id'] : null;
            $qty = round(max(0, (float) ($item['qty'] ?? 0)), 3);
            $rate = round(max(0, (float) ($item['rate'] ?? 0)), 2);
            $lineTotal = round($qty * $rate, 2);

            if ($qty <= 0) {
                continue;
            }

            if (! $productId && ! $description) {
                continue;
            }

            $normalized[] = [
                'product_id' => $productId,
                'description' => $description ?: null,
                'qty' => $qty,
                'unit_id' => $unitId,
                'rate' => $rate,
                'line_total' => $lineTotal,
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *   reference_type:SupplierReturnReferenceType,
     *   reference_id:?int,
     *   supplier_id:?int,
     *   supplier_bill_id:?int,
     *   stock_receive_id:?int,
     *   purchase_order_id:?int,
     *   available_qty_map:array<int,float>
     * }
     */
    protected function resolveReferenceContext(array $payload, ?int $excludeReturnId = null): array
    {
        $referenceType = $this->resolveReferenceType($payload['reference_type'] ?? SupplierReturnReferenceType::MANUAL->value);
        $supplierBillId = $this->nullableInt($payload['supplier_bill_id'] ?? null);
        $stockReceiveId = $this->nullableInt($payload['stock_receive_id'] ?? null);
        $purchaseOrderId = $this->nullableInt($payload['purchase_order_id'] ?? null);

        if ($referenceType === SupplierReturnReferenceType::SUPPLIER_BILL) {
            if (! $supplierBillId) {
                throw new \DomainException('Supplier bill is required for bill-linked return.');
            }

            $bill = SupplierBill::query()
                ->with('items')
                ->lockForUpdate()
                ->find($supplierBillId);

            if (! $bill) {
                throw new \DomainException('Selected supplier bill was not found.');
            }

            return [
                'reference_type' => $referenceType,
                'reference_id' => (int) $bill->id,
                'supplier_id' => (int) $bill->supplier_id,
                'supplier_bill_id' => (int) $bill->id,
                'stock_receive_id' => $bill->stock_receive_id ? (int) $bill->stock_receive_id : null,
                'purchase_order_id' => $bill->purchase_order_id ? (int) $bill->purchase_order_id : null,
                'available_qty_map' => $this->buildAvailableQuantityMap(
                    sourceItems: $bill->items,
                    referenceType: $referenceType,
                    referenceId: (int) $bill->id,
                    excludeReturnId: $excludeReturnId,
                    quantityResolver: fn ($item): float => (float) $item->qty,
                    productResolver: fn ($item): ?int => $item->product_id ? (int) $item->product_id : null
                ),
            ];
        }

        if ($referenceType === SupplierReturnReferenceType::STOCK_RECEIVE) {
            if (! $stockReceiveId) {
                throw new \DomainException('Stock receive is required for stock-linked return.');
            }

            $stockReceive = StockReceive::query()
                ->with('items')
                ->lockForUpdate()
                ->find($stockReceiveId);

            if (! $stockReceive) {
                throw new \DomainException('Selected stock receive was not found.');
            }

            return [
                'reference_type' => $referenceType,
                'reference_id' => (int) $stockReceive->id,
                'supplier_id' => (int) $stockReceive->supplier_id,
                'supplier_bill_id' => null,
                'stock_receive_id' => (int) $stockReceive->id,
                'purchase_order_id' => $stockReceive->purchase_order_id ? (int) $stockReceive->purchase_order_id : null,
                'available_qty_map' => $this->buildAvailableQuantityMap(
                    sourceItems: $stockReceive->items,
                    referenceType: $referenceType,
                    referenceId: (int) $stockReceive->id,
                    excludeReturnId: $excludeReturnId,
                    quantityResolver: fn ($item): float => (float) $item->quantity,
                    productResolver: fn ($item): ?int => $item->product_id ? (int) $item->product_id : null
                ),
            ];
        }

        if ($referenceType === SupplierReturnReferenceType::PURCHASE_ORDER) {
            if (! $purchaseOrderId) {
                throw new \DomainException('Purchase order is required for PO-linked return.');
            }

            $purchaseOrder = PurchaseOrder::query()
                ->with('items')
                ->lockForUpdate()
                ->find($purchaseOrderId);

            if (! $purchaseOrder) {
                throw new \DomainException('Selected purchase order was not found.');
            }

            return [
                'reference_type' => $referenceType,
                'reference_id' => (int) $purchaseOrder->id,
                'supplier_id' => (int) $purchaseOrder->supplier_id,
                'supplier_bill_id' => null,
                'stock_receive_id' => null,
                'purchase_order_id' => (int) $purchaseOrder->id,
                'available_qty_map' => $this->buildAvailableQuantityMap(
                    sourceItems: $purchaseOrder->items,
                    referenceType: $referenceType,
                    referenceId: (int) $purchaseOrder->id,
                    excludeReturnId: $excludeReturnId,
                    quantityResolver: fn ($item): float => (float) ($item->approved_quantity ?: $item->quantity),
                    productResolver: fn ($item): ?int => $item->product_id ? (int) $item->product_id : null
                ),
            ];
        }

        return [
            'reference_type' => SupplierReturnReferenceType::MANUAL,
            'reference_id' => null,
            'supplier_id' => null,
            'supplier_bill_id' => null,
            'stock_receive_id' => null,
            'purchase_order_id' => null,
            'available_qty_map' => [],
        ];
    }

    /**
     * @param  array<int, array{product_id:?int,description:?string,qty:float,unit_id:?int,rate:float,line_total:float}>  $items
     * @param  array<int, float>  $availableQtyMap
     */
    protected function validateSourceQuantities(array $items, array $availableQtyMap): void
    {
        if ($availableQtyMap === []) {
            return;
        }

        $requested = [];

        foreach ($items as $item) {
            $productId = $item['product_id'] ? (int) $item['product_id'] : null;

            if (! $productId) {
                throw new \DomainException('Linked reference items must include a product.');
            }

            if (! array_key_exists($productId, $availableQtyMap)) {
                throw new \DomainException('One or more products are not available in selected reference source.');
            }

            $requested[$productId] = round(($requested[$productId] ?? 0) + (float) $item['qty'], 3);
        }

        foreach ($requested as $productId => $qty) {
            $available = round((float) ($availableQtyMap[$productId] ?? 0), 3);

            if ($qty - $available > 0.0001) {
                throw new \DomainException('Return quantity cannot exceed source available quantity for selected item(s).');
            }
        }
    }

    /**
     * @param  Collection<int, mixed>  $sourceItems
     * @param  callable  $quantityResolver
     * @param  callable  $productResolver
     * @return array<int, float>
     */
    protected function buildAvailableQuantityMap(
        Collection $sourceItems,
        SupplierReturnReferenceType $referenceType,
        int $referenceId,
        ?int $excludeReturnId,
        callable $quantityResolver,
        callable $productResolver
    ): array {
        $sourceMap = [];

        foreach ($sourceItems as $sourceItem) {
            $productId = $productResolver($sourceItem);

            if (! $productId) {
                continue;
            }

            $sourceQty = round(max(0, (float) $quantityResolver($sourceItem)), 3);
            $sourceMap[$productId] = round(($sourceMap[$productId] ?? 0) + $sourceQty, 3);
        }

        if ($sourceMap === []) {
            return [];
        }

        $usedMap = SupplierReturnItem::query()
            ->from('supplier_return_items as sri')
            ->join('supplier_returns as sr', 'sr.id', '=', 'sri.supplier_return_id')
            ->where('sr.status', SupplierReturnStatus::APPROVED->value)
            ->where('sr.reference_type', $referenceType->value)
            ->where('sr.reference_id', $referenceId)
            ->when($excludeReturnId, fn ($query) => $query->where('sr.id', '!=', $excludeReturnId))
            ->whereNotNull('sri.product_id')
            ->groupBy('sri.product_id')
            ->selectRaw('sri.product_id')
            ->selectRaw('COALESCE(SUM(sri.qty), 0) as used_qty')
            ->pluck('used_qty', 'product_id');

        $availableMap = [];

        foreach ($sourceMap as $productId => $sourceQty) {
            $usedQty = round((float) ($usedMap[$productId] ?? 0), 3);
            $availableMap[(int) $productId] = round(max(0, $sourceQty - $usedQty), 3);
        }

        return $availableMap;
    }

    protected function resolveReferenceType(mixed $value): SupplierReturnReferenceType
    {
        if ($value instanceof SupplierReturnReferenceType) {
            return $value;
        }

        try {
            return SupplierReturnReferenceType::from((string) $value);
        } catch (\Throwable) {
            return SupplierReturnReferenceType::MANUAL;
        }
    }

    protected function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $resolved = (int) $value;

        return $resolved > 0 ? $resolved : null;
    }

    protected function resolveActorId(?int $actorId = null): int
    {
        $resolved = $actorId ?? auth()->id();

        if (! $resolved) {
            throw new \DomainException('Unable to resolve authenticated user for this action.');
        }

        return (int) $resolved;
    }
}
