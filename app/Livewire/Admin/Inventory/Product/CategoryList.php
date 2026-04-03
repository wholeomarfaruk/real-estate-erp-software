<?php

namespace App\Livewire\Admin\Inventory\Product;

use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\ProductCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CategoryList extends Component
{
    use InteractsWithInventoryAccess;

    public string $search = '';

    public ?int $editingId = null;

    public string $name = '';

    public ?int $parent_id = null;

    public ?string $description = null;

    public bool $status = true;

    public function mount(): void
    {
        $this->authorizePermission('inventory.product.view');
    }

    public function edit(int $categoryId): void
    {
        $this->authorizePermission('inventory.product.update');

        $category = ProductCategory::query()->findOrFail($categoryId);

        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->parent_id = $category->parent_id;
        $this->description = $category->description;
        $this->status = (bool) $category->status;
    }

    public function save(): void
    {
        $this->authorizePermission($this->editingId ? 'inventory.product.update' : 'inventory.product.create');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:product_categories,id',
                Rule::notIn([$this->editingId]),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($validated): void {
            ProductCategory::query()->updateOrCreate(
                ['id' => $this->editingId],
                [
                    'name' => $validated['name'],
                    'slug' => $this->uniqueSlug($validated['name']),
                    'parent_id' => $validated['parent_id'],
                    'description' => $validated['description'],
                    'status' => $validated['status'],
                ]
            );
        });

        $this->resetForm();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Category saved successfully.']);
    }

    public function delete(int $categoryId): void
    {
        $this->authorizePermission('inventory.product.delete');

        $category = ProductCategory::query()->find($categoryId);

        if (! $category) {
            return;
        }

        if ($category->children()->exists() || $category->products()->exists()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Category has child/category product dependencies.']);

            return;
        }

        DB::transaction(function () use ($category): void {
            $category->delete();
        });

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Category deleted successfully.']);
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'parent_id', 'description', 'status']);
        $this->status = true;
    }

    public function render(): View
    {
        $categories = ProductCategory::query()
            ->with('parent:id,name')
            ->when($this->search !== '', function (Builder $query): void {
                $query->where('name', 'like', '%'.$this->search.'%');
            })
            ->orderBy('name')
            ->get();

        return view('livewire.admin.inventory.product.category-list', [
            'categories' => $categories,
            'parents' => ProductCategory::query()
                ->when($this->editingId, fn (Builder $query): Builder => $query->where('id', '!=', $this->editingId))
                ->orderBy('name')
                ->get(['id', 'name']),
        ])->layout('layouts.admin.admin');
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (ProductCategory::query()
            ->where('slug', $slug)
            ->when($this->editingId, fn (Builder $query): Builder => $query->where('id', '!=', $this->editingId))
            ->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
