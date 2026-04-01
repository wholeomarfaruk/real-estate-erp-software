<?php
namespace App\Livewire\Admin\Materials;

use App\Livewire\Traits\WithMediaPicker;
use Livewire\Component;
use App\Models\ProductBrand;
use Illuminate\Support\Str;

use Livewire\WithPagination;

class ProductBrands extends Component
{
    use WithPagination, WithMediaPicker;

    public $search = '';
    public $name;
    public $description;
    public $image_id;
    public $editingId = null;

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'image_id' => 'nullable|exists:files,id',
    ];

    protected $paginationTheme = 'tailwind';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $brands = ProductBrand::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.admin.materials.product-brands', compact('brands'))
            ->layout('layouts.admin.admin');
    }

    public function edit(int $id): void
    {
        $brand = ProductBrand::findOrFail($id);
        $this->editingId = $brand->id;
        $this->name = $brand->name;
        $this->description = $brand->description;
        $this->image_id = $brand->image_id;
    }

    public function save(): void
    {
        $data = $this->validate();
        $data['slug'] = $this->uniqueSlug($this->name, $this->editingId);

        ProductBrand::updateOrCreate(
            ['id' => $this->editingId],
            $data
        );

        $this->resetForm();
        session()->flash('success', 'Brand saved successfully.');
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Brand saved successfully.']);
    }

    public function delete(int $id): void
    {
        $brand = ProductBrand::find($id);
        if (!$brand) {
            return;
        }

        if ($brand->products()->exists()) {
            session()->flash('error', 'Brand has products and cannot be removed.');
            return;
        }

        $brand->delete();
        session()->flash('success', 'Brand deleted.');
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Brand deleted.']);
    }

    public function resetForm(): void
    {
        $this->reset(['name', 'description', 'image_id', 'editingId']);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $base = $slug;
        $counter = 1;

        while (ProductBrand::where('slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base . '-' . $counter++;
        }

        return $slug;
    }
}