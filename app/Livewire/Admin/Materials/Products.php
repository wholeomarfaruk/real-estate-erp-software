<?php
namespace App\Livewire\Admin\Materials;

use App\Livewire\Traits\WithMediaPicker;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\ProductVariant;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Products extends Component
{
    use WithPagination, WithMediaPicker;
    public $search = '';
    public $name;
    public $category_id;
    public $brand_id;
    public $unit;
    public $description;
    public $image_id;
    public $editingId = null;
    public $variantProductId;
    public $variantName;
    public $variantDescription;
    public $variantImageId;
    public $variantEditingId = null;
    public $addCategoryModalOpen = false;
    public $categoryName, $categoryParentId;
    public $addBrandModalOpen = false;
    public $brandName, $brandDescription, $brandImageId;
    public $addUnitModalOpen = false;
    public $unitName;
    public $productVarientModalOpen = false;
    public $productVariants = [];
    public $selectedVariantProduct;
    public $addVariantModalOpen = false;
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

        $categories = ProductCategory::with('children')->whereNull('parent_id')->orderBy('name')->get();
        $brands = ProductBrand::orderBy('name')->get();
        $units= ProductUnit::orderBy('name')->get();

        return view('livewire.admin.materials.products', compact('products', 'categories', 'brands', 'units'))
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
        $this->image_id = $product->image_id;
    }

    public function saveProduct(): void
    {
        $data = $this->validate($this->productRules());
      
        Product::updateOrCreate(
            ['id' => $this->editingId],
            $data
        );

        $this->resetProductForm();
     
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Product saved successfully.']);
    }

    public function deleteProduct(int $id): void
    {
        $product = Product::find($id);
        if (!$product) {
            return;
        }

        $product->delete();
       
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Product deleted.']);
    }

    public function startVariant(int $productId): void
    {

        $this->selectedVariantProduct = Product::find($productId);
        if(!$this->selectedVariantProduct){
          $this->dispatch('toast', ['type' => 'error', 'message' => 'Product not found.']);
            return;
        }
        $this->variantProductId = $productId;
        $this->productVarientModalOpen = true;
        $this->variantEditingId = null;
        $this->variantName = null;
        $this->variantDescription = null;
        $this->variantImageId = null;
        $this->productVariants = ProductVariant::where('product_id', $productId)->get();

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
       $this->productVariants = ProductVariant::where('product_id', $this->variantProductId)->get();
       $this->addVariantModalOpen = false;
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
            'image_id' => 'nullable|exists:files,id',
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
    public function saveCategory(): void
    {
        $data = $this->validate([
            'categoryName' => 'required|string|max:255',
            'categoryParentId' => 'nullable|exists:product_categories,id',
        ]);
        $slug = Str::slug($this->brandName);
            if(ProductBrand::where('slug', $slug)->exists()){
                $data['slug'] = $slug . '-' . Str::random(5);
            }

        ProductCategory::create(
            [
                'name' => $this->categoryName,
                'parent_id' => $this->categoryParentId,
                'slug' => $slug,
            ]
        );

        $this->reset(['categoryName', 'categoryParentId']);
        $this->addCategoryModalOpen = false;

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Category created successfully.']);
    }
        public function saveBrand(): void
        {
            $data = $this->validate([
                'brandName' => 'required|string|max:255',
                'brandDescription' => 'nullable|string',
                'brandImageId' => 'nullable|exists:files,id',
            ]);
            $slug = Str::slug($this->brandName);
            if(ProductBrand::where('slug', $slug)->exists()){
                $data['slug'] = $slug . '-' . Str::random(5);
            }
    
            ProductBrand::create(
                [
                    'name' => $this->brandName,
                    'description' => $this->brandDescription,
                    'image_id' => $this->brandImageId,
                    'slug' => $slug,
                ]
            );
    
            $this->reset(['brandName', 'brandDescription', 'brandImageId']);
            $this->addBrandModalOpen = false;
    
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Brand created successfully.']);
        }
        public function saveUnit(): void
        {
            $data = $this->validate([
                'unitName' => 'required|string|max:255|unique:product_units,name',
            ]);
    
            ProductUnit::create(
                [
                    'name' =>Str::slug($this->unitName),
                ]
            );
    
            $this->reset(['unitName']);
            $this->addUnitModalOpen = false;
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Unit created successfully.']);
        }
}
