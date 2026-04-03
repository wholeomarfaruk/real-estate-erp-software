<?php

namespace App\Livewire\Admin\Inventory\Product;

use App\Enums\Inventory\ProductStatus;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ProductForm extends Component
{
    use InteractsWithInventoryAccess;

    public ?Product $productRecord = null;

    public ?int $productId = null;

    public bool $editMode = false;

    public string $name = '';

    public ?string $sku = null;

    public ?int $category_id = null;

    public ?int $product_unit_id = null;

    public ?string $description = null;

    public float $minimum_stock_level = 0;

    public string $status = 'active';

    public function mount(?Product $product = null): void
    {
        if ($product && $product->exists) {
            $this->authorizePermission('inventory.product.update');

            $this->editMode = true;
            $this->productRecord = $product;
            $this->productId = $product->id;
            $this->name = $product->name;
            $this->sku = $product->sku;
            $this->category_id = $product->category_id;
            $this->product_unit_id = $product->product_unit_id;
            $this->description = $product->description;
            $this->minimum_stock_level = (float) $product->minimum_stock_level;
            $this->status = $product->status?->value ?? ProductStatus::ACTIVE->value;

            return;
        }

        $this->authorizePermission('inventory.product.create');
    }

    public function updatedName(string $value): void
    {
        if (! $this->editMode && (! $this->sku || trim($this->sku) === '')) {
            $this->sku = Str::upper(Str::slug($value, '-'));
        }
    }

    public function save()
    {
        if ($this->editMode) {
            $this->authorizePermission('inventory.product.update');
        } else {
            $this->authorizePermission('inventory.product.create');
        }

        $validated = $this->validate($this->rules(), $this->messages());

        if (! $validated['sku']) {
            $validated['sku'] = $this->generateSku($validated['name']);
        }

        DB::transaction(function () use ($validated): void {
            if ($this->editMode && $this->productRecord) {
                $this->productRecord->update($validated);

                return;
            }

            Product::query()->create($validated);
        });

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => $this->editMode ? 'Product updated successfully.' : 'Product created successfully.',
        ]);

        return redirect()->route('admin.inventory.products.index');
    }

    public function render(): View
    {
        return view('livewire.admin.inventory.product.product-form', [
            'categories' => ProductCategory::query()->active()->orderBy('name')->get(['id', 'name']),
            'units' => ProductUnit::query()->active()->orderBy('name')->get(['id', 'name']),
            'statuses' => ProductStatus::cases(),
        ])->layout('layouts.admin.admin');
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($this->productId)],
            'category_id' => ['required', 'integer', 'exists:product_categories,id'],
            'product_unit_id' => ['required', 'integer', 'exists:product_units,id'],
            'description' => ['nullable', 'string'],
            'minimum_stock_level' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
        ];
    }

    protected function messages(): array
    {
        return [
            'category_id.required' => 'Please select a product category.',
            'product_unit_id.required' => 'Please select a unit.',
        ];
    }

    protected function generateSku(string $name): string
    {
        $base = Str::upper(Str::slug($name, '-'));
        $sku = $base;
        $counter = 1;

        while (Product::query()
            ->where('sku', $sku)
            ->when($this->productId, fn ($query) => $query->where('id', '!=', $this->productId))
            ->exists()) {
            $sku = $base.'-'.$counter++;
        }

        return $sku;
    }
}
