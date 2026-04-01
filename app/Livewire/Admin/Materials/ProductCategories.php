<?php

namespace App\Livewire\Admin\Materials;


use Livewire\Component;
use App\Models\ProductCategory;
use Illuminate\Support\Str;
use Livewire\WithPagination;

class ProductCategories extends Component
{
    use WithPagination;

    public $search = '';
    public $name;
    public $description;
    public $parent_id;
    public $image_id;
    public $editingId = null;
    public $editMode=false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'parent_id' => 'nullable|exists:product_categories,id',
      
    ];

    protected $paginationTheme = 'tailwind';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }
    public function render()
    {
            $categories = ProductCategory::query()
            ->with('parent')
            ->when(empty($this->search) && !$this->search, fn($q) => $q->where('parent_id', null))

            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->orderByDesc('id')
            ->get();

        $parents = ProductCategory::query()
        ->when($this->editingId, fn($q) => $q->where('id', '!=', $this->editingId))
        ->orderBy('name')->get();
        return view('livewire.admin.materials.product-categories', compact('categories', 'parents'))->layout('layouts.admin.admin');
    }
    
    public function edit(int $id): void
    {
        $category = ProductCategory::find($id);
        if(!$category){
             $this->dispatch('toast', ['type' => 'error', 'message' => 'Category not found.']);
            return;
        }
        
        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->description = $category->description;
        $this->parent_id = $category->parent_id;
        $this->editMode = true;
    }

    public function save(): void
    {
        $data = $this->validate();
        $data['slug'] = $this->uniqueSlug($this->name, $this->editingId);

        ProductCategory::updateOrCreate(
            ['id' => $this->editingId],
            $data
        );

        $this->resetForm();
        session()->flash('success', 'Category saved successfully.');
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Category saved successfully.']);
    }

    public function delete(int $id): void
    {
        $category = ProductCategory::find($id);
        if (!$category) {
            return;
        }

        if ($category->children()->exists() || $category->products()->exists()) {
            session()->flash('error', 'Category has child categories or products and cannot be removed.');
            return;
        }

        $category->delete();
        session()->flash('success', 'Category deleted.');
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Category deleted.']);
    }

    public function resetForm(): void
    {
        $this->reset(['name', 'description', 'parent_id', 'image_id', 'editingId', 'editMode']);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $base = $slug;
        $counter = 1;

        while (ProductCategory::where('slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base . '-' . $counter++;
        }

        return $slug;
    }
}
