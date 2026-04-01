<?php
namespace App\Livewire\Admin\Materials;

use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use Livewire\Component;
use Livewire\WithPagination;

class Products extends Component
{
    use WithPagination;

    public $search = '';

    public $name;
    public $category_id;
    public $brand_id;
    public $unit;
    public $description;
    public $editingId = null;

    public $variantProductId;
    public $variantName;
    public $variantDescription;
    public $variantImageId;
    public $variantEditingId = null;

    protected $paginationTheme = 'tailwind';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $products = Product::query()
            ->with(['category', 'brand', 'variants'])
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderByDesc('id')
            ->paginate(10);

        $categories = ProductCategory::orderBy('name')->get();
        $brands = ProductBrand::orderBy('name')->get();

        return view('livewire.admin.materials.products', compact('products', 'categories', 'brands'))
            ->layout('layouts.admin.admin');
    }

    public function editProduct(int $id): void
    {
        $product = Product::findOrFail($id);
        $this->editingId = $product->id;
        $this->name = $product->name;
        $this->category_id = $product->category_id;
        $this->brand_id = $product->brand_id;
        $this->unit = $product->unit;
        $this->description = $product->description;
    }

    public function saveProduct(): void
    {
        $data = $this->validate($this->productRules());

        Product::updateOrCreate(
            ['id' => $this->editingId],
            $data
        );

        $this->resetProductForm();
        session()->flash('success', 'Product saved successfully.');
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Product saved successfully.']);
    }

    public function deleteProduct(int $id): void
    {
        $product = Product::find($id);
        if (!$product) {
            return;
        }

        $product->delete();
        session()->flash('success', 'Product deleted.');
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Product deleted.']);
    }

    public function startVariant(int $productId): void
    {
        $this->variantProductId = $productId;
        $this->variantEditingId = null;
        $this->variantName = null;
        $this->variantDescription = null;
        $this->variantImageId = null;
    }

    public function editVariant(int $variantId): void
    {
        $variant = ProductVariant::findOrFail($variantId);
        $this->variantEditingId = $variant->id;
        $this->variantProductId = $variant->product_id;
        $this->variantName = $variant->name;
        $this->variantDescription = $variant->description;
        $this->variantImageId = $variant->image_id;
    }

    public function saveVariant(): void
    {
        $this->validate($this->variantRules());

        ProductVariant::updateOrCreate(
            ['id' => $this->variantEditingId],
            [
                'product_id' => $this->variantProductId,
                'name' => $this->variantName,
                'description' => $this->variantDescription,
                'image_id' => $this->variantImageId,
            ]
        );

        $this->resetVariantForm();
        session()->flash('success', 'Variant saved successfully.');
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Variant saved successfully.']);
    }

    public function deleteVariant(int $variantId): void
    {
        $variant = ProductVariant::find($variantId);
        if (!$variant) {
            return;
        }

        $variant->delete();
        session()->flash('success', 'Variant deleted.');
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Variant deleted.']);
    }

    protected function productRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:product_categories,id',
            'brand_id' => 'nullable|exists:product_brands,id',
            'unit' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ];
    }

    protected function variantRules(): array
    {
        return [
            'variantProductId' => 'required|exists:products,id',
            'variantName' => 'required|string|max:255',
            'variantDescription' => 'nullable|string',
            'variantImageId' => 'nullable|exists:files,id',
        ];
    }

    protected function resetProductForm(): void
    {
        $this->reset(['name', 'category_id', 'brand_id', 'unit', 'description', 'editingId']);
    }

    protected function resetVariantForm(): void
    {
        $productId = $this->variantProductId;
        $this->reset(['variantName', 'variantDescription', 'variantImageId', 'variantEditingId']);
        $this->variantProductId = $productId;
    }
}
