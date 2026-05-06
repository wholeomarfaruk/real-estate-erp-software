<?php

namespace App\Livewire\Admin\Inventory\PurchaseOrder;

use App\Enums\Inventory\PurchaseOrderStatus;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\PurchaseOrder;
use App\Services\Inventory\PurchaseOrderService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PurchaseSettlementForm extends Component
{
    use InteractsWithInventoryAccess;

    public PurchaseOrder $purchaseOrder;

    public float|int|string $actual_purchase_amount = 0;

    public float|int|string $returned_cash_amount = 0;

    public ?string $remarks = null;

    public function mount(PurchaseOrder $purchaseOrder): void
    {
        $this->authorizePermission('inventory.purchase_order.settle');

        $this->purchaseOrder = $purchaseOrder->load([
            'store:id,name,code,type',
            'supplier:id,name',
            'funds',
            'settlement',
        ]);

        $this->ensurePurchaseOrderAccessible($this->purchaseOrder);

        $released = (float) $this->purchaseOrder->funds->sum(fn ($fund): float => (float) $fund->amount);

        $this->actual_purchase_amount = (float) ($this->purchaseOrder->settlement?->actual_purchase_amount
            ?? $this->purchaseOrder->actual_purchase_amount
            ?? $released);
        $this->returned_cash_amount = (float) ($this->purchaseOrder->settlement?->returned_cash_amount ?? 0);
        $this->remarks = $this->purchaseOrder->settlement?->remarks;
    }

    public function save()
    {
        $this->authorizePermission('inventory.purchase_order.settle');

        $validated = $this->validate($this->rules(), $this->messages());

        try {
            app(PurchaseOrderService::class)->settlePurchaseOrder($this->purchaseOrder, $validated, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase settlement saved successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);

            return;
        }

        return redirect()->route('admin.inventory.purchase-orders.view', $this->purchaseOrder);
    }

    public function saveAndComplete()
    {
        $this->authorizePermission('inventory.purchase_order.settle');
        $this->authorizePermission('inventory.purchase_order.complete');

        $validated = $this->validate($this->rules(), $this->messages());

        try {
            $service = app(PurchaseOrderService::class);
            $service->settlePurchaseOrder($this->purchaseOrder, $validated, (int) auth()->id());
            $service->completePurchaseOrder($this->purchaseOrder, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Settlement saved and purchase order completed.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);

            return;
        }

        return redirect()->route('admin.inventory.purchase-orders.view', $this->purchaseOrder);
    }

    public function render(): View
    {
        $this->purchaseOrder->load(['funds', 'settlement']);

        $totalFundReleased = (float) $this->purchaseOrder->funds->sum(fn ($fund): float => (float) $fund->amount);
        $calculatedDue = max(0, round($this->actual_purchase_amount - $totalFundReleased, 2));

        return view('livewire.admin.inventory.purchase-order.purchase-settlement-form', [
            'totalFundReleased' => round($totalFundReleased, 2),
            'calculatedDue' => round($calculatedDue, 2),
            'canCompleteNow' => $this->purchaseOrder->status === PurchaseOrderStatus::RECEIVED,
        ])->layout('layouts.admin.admin');
    }

    protected function rules(): array
    {
        return [
            'actual_purchase_amount' => ['required', 'numeric', 'min:0'],
            'returned_cash_amount' => ['nullable', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'actual_purchase_amount.required' => 'Actual purchase amount is required.',
        ];
    }

    protected function ensurePurchaseOrderAccessible(PurchaseOrder $purchaseOrder): void
    {
        if ($this->canViewAllStores()) {
            return;
        }

        $storeIds = $this->getAccessibleStoreIds();

        abort_unless(
            in_array((int) $purchaseOrder->store_id, $storeIds, true),
            403,
            'You are not allowed to access this purchase order.'
        );
    }

    protected function canViewAllStores(): bool
    {
        return $this->hasInventoryWideAccess($this->purchaseOrderGlobalAccessPermissions());
    }
}
