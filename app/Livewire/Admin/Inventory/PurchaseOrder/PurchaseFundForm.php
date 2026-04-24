<?php

namespace App\Livewire\Admin\Inventory\PurchaseOrder;

use App\Enums\Inventory\PurchaseFundReleaseType;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Services\Inventory\PurchaseOrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PurchaseFundForm extends Component
{
    use InteractsWithInventoryAccess;

    public PurchaseOrder $purchaseOrder;

    public string $release_type = 'cash';

    public float|int|string $amount = 0;

    public ?int $received_by = null;

    public string $release_date = '';

    public ?string $remarks = null;

    public function mount(PurchaseOrder $purchaseOrder): void
    {
        $this->authorizePermission('inventory.purchase_order.fund_release');

        $this->purchaseOrder = $purchaseOrder->load([
            'store:id,name,code,type',
            'supplier:id,name',
            'funds.releaser:id,name',
            'funds.receiver:id,name',
        ]);

        $this->ensurePurchaseOrderAccessible($this->purchaseOrder);

        $this->release_date = now()->toDateString();
    }

    public function save()
    {
        $this->authorizePermission('inventory.purchase_order.fund_release');

        $validated = $this->validate($this->rules(), $this->messages());

        try {
            app(PurchaseOrderService::class)->releaseFund($this->purchaseOrder, $validated, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Fund release saved successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);

            return;
        }

        return redirect()->route('admin.inventory.purchase-orders.view', $this->purchaseOrder);
    }

    public function render(): View
    {
        $this->purchaseOrder->load([
            'funds' => fn ($query) => $query
                ->with(['releaser:id,name', 'receiver:id,name'])
                ->latest('release_date')
                ->latest('id'),
        ]);

        $totalReleased = (float) $this->purchaseOrder->funds->sum(fn ($fund): float => (float) $fund->amount);

        return view('livewire.admin.inventory.purchase-order.purchase-fund-form', [
            'releaseTypes' => PurchaseFundReleaseType::cases(),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'totalReleased' => round($totalReleased, 2),
        ])->layout('layouts.admin.admin');
    }

    protected function rules(): array
    {
        return [
            'release_type' => ['required', Rule::in(array_map(fn (PurchaseFundReleaseType $type): string => $type->value, PurchaseFundReleaseType::cases()))],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'received_by' => ['nullable', 'integer', 'exists:users,id'],
            'release_date' => ['required', 'date'],
            'remarks' => ['nullable', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'amount.min' => 'Released amount must be greater than zero.',
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
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('superadmin')
            || $user->hasRole('admin')
            || $user->hasRole('accounts')
            || $user->hasRole('engineers')
            || $user->hasRole('chairman')
            || $user->hasRole('md')
            || $user->can('inventory.purchase_order.accounts_approve');
    }
}
