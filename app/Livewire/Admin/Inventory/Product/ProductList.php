<?php

namespace App\Livewire\Admin\Inventory\Product;

use App\Enums\Inventory\ProductStatus;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ProductList extends Component
{
    use InteractsWithInventoryAccess;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public ?int $categoryFilter = null;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('inventory.product.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $productId): void
    {
        $this->authorizePermission('inventory.product.update');

        $product = Product::query()->find($productId);

        if (! $product) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Product not found.']);

            return;
        }

        $nextStatus = $product->status === ProductStatus::ACTIVE
            ? ProductStatus::INACTIVE
            : ProductStatus::ACTIVE;

        DB::transaction(function () use ($product, $nextStatus): void {
            $product->update([
                'status' => $nextStatus->value,
            ]);
        });

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Product status updated successfully.']);
    }

    public function deleteProduct(int $productId): void
    {
        $this->authorizePermission('inventory.product.delete');

        $product = Product::query()->find($productId);

        if (! $product) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Product not found.']);

            return;
        }

        if ($product->stockMovements()->exists()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Product has stock movements and cannot be deleted.']);

            return;
        }

        DB::transaction(function () use ($product): void {
            $product->delete();
        });

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Product deleted successfully.']);
    }

    public function render(): View
    {
        $this->authorizePermission('inventory.product.view');

        $products = Product::query()
            ->with(['category:id,name', 'unit:id,name'])
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $subQuery): void {
                    $subQuery
                        ->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('sku', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter !== '', function (Builder $query): void {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->categoryFilter, function (Builder $query): void {
                $query->where('category_id', $this->categoryFilter);
            })
            ->latest()
            ->paginate(15);

        return view('livewire.admin.inventory.product.product-list', [
            'products' => $products,
            'statuses' => ProductStatus::cases(),
            'categories' => \App\Models\ProductCategory::query()->orderBy('name')->get(['id', 'name']),
        ])->layout('layouts.admin.admin');
    }
}
