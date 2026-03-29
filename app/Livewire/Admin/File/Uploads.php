<?php

namespace App\Livewire\Admin\File;

use App\Http\Controllers\Admin\FileUploadController;
use App\Models\File;
use App\Models\FileItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;



class Uploads extends Component
{
    use WithPagination;

    public $fileinput = [];
    public $selected = [];
    public $featured_image;

    protected $listeners = ['mediaSelected'];
    use WithPagination; // ✅ enable pagination

    public $search = ''; // optional: for search/filter
    public $filterType = ''; // for filtering by file type

    protected $paginationTheme = 'tailwind'; // 'bootstrap' also works

    public function updatingSearch()
    {
        $this->resetPage(); // reset page on search update
    }

    public function updatingFilterType()
    {
        $this->resetPage(); // reset page on filter update
    }
    public function mediaSelected($field, $id)
    {
        if (is_array($id)) {
            $this->$field = array_unique(array_merge($this->$field ?? [], $id));
        }
        // $this->{$field} = array_merge($this->{$field} ?? [], (array)$id);
    }
    public function render()
    {
        $images = File::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->when($this->filterType, function ($query) {
                $query->where('type', $this->filterType);
            })
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->paginate(20); // 10 items per page
        return view('livewire.admin.file.uploads', ['images' => $images])->layout('layouts.admin.admin');
    }
    public function removeMedia($field, $id = null)
    {
        if ($id && is_array($this->$field)) {
            // remove specific file from multiple selection
            $this->{$field} = array_filter($this->{$field}, fn($i) => $i != $id);
        } else {
            // single file
            $this->$field = null;
        }
    }
public function selectImage($id)
{
    if (in_array($id, $this->selected)) {

        // remove
        $this->selected = array_values(array_diff($this->selected, [$id]));

    } else {

        // add
        $this->selected[$id] = $id;

    }
    Log::debug(['selected' => $this->selected]);
}
    public function delete($id)
    {
        $file = File::find($id);

        if ($file) {

            $item = FileItem::where('file_id', $file->id)->first();

            if ($item) {
                Storage::disk('public')->delete($item->path);
                $item->delete();
            }

            $file->delete();

        }
    }

}
