<?php

namespace App\Livewire\Admin\Materials;

use App\Models\ProductUnit;
use Illuminate\Support\Str;
use Livewire\Component;

class ProductUnits extends Component
{
    public $search = '';
    public $name;
    public $editingId = null;

    protected $rules = [
        'name' => 'required|string|max:255',
    ];

    protected $paginationTheme = 'tailwind';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }
    public function render(){
        $units = ProductUnit::paginate(10);
        return view('livewire.admin.materials.product-units',compact('units'))->layout('layouts.admin.admin');
    }
    public function edit(int $id): void
    {
        $unit = ProductUnit::findOrFail($id);
        if(!$unit){
                $this->dispatch('toast', ['type' => 'error', 'message' => 'Unit Item not found in Database.']);
        }
        $this->editingId = $unit->id;
        $this->name = $unit->name;
    }

    public function save(): void
    {
   
        $data['name'] =$this->name;

        ProductUnit::updateOrCreate(
            ['id' => $this->editingId],
            $data
        );

        $this->resetForm();
        session()->flash('success', 'Unit saved successfully.');
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Unit saved successfully.']);
    }

    public function delete(int $id): void
    {
        $unit = ProductUnit::find($id);
        if (!$unit) {
            return;
        }

        if ($unit->products()->exists()) {
                    $this->dispatch('toast', ['type' => 'error', 'message' => 'Unit has products and cannot be removed.']);
            return;
        }

        $unit->delete();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Unit deleted.']);
    }

    public function resetForm(): void
    {
        $this->reset(['name', 'editingId']);
    }
    public function updatedName($name){
        $this->name = Str::slug($name);
        return $this->name;
    }


}
