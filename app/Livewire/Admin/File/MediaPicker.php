<?php

namespace App\Livewire\Admin\File;

use Livewire\Component;
use App\Models\File;
use App\Models\FileItem;
use Illuminate\Support\Facades\Storage;
use Livewire\WithPagination;
class MediaPicker extends Component
{
    public $mediapickerModal = false;
    public $selected = [];
    public $multiple = false;
    public $showModal = false;
    public $target;
    public $type;
    protected $listeners = ['openMediaPicker','fileUploaded'];
    public $search = ''; // optional: for search/filter
 use WithPagination; // ✅ enable pagination
    protected $paginationTheme = 'tailwind'; // 'bootstrap' also works
    public function updatingSearch()
    {
        $this->resetPage(); // reset page on search update
    }
    public function openMediaPicker($target = null, $multiple = false, $type = null)
    {
        $this->target = $target;
        $this->multiple = $multiple;
        $this->type = $type;
        $this->mediapickerModal = true;
        $this->reset('selected');
    }
    public function render()
    {
        $files = File::query()
            ->when($this->type, function ($query) {
                $query->where('type', $this->type);
            })
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('livewire.admin.file.media-picker', compact('files'));
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
            $this->files = File::all();
        }
    }
    public function selectImage($id)
    {
        if ($this->multiple) {

            if (in_array($id, $this->selected)) {
                $this->selected = array_diff($this->selected, [$id]);
            } else {
                $this->selected[] = $id;
            }

        } else {

            $this->selected = [$id];


        }

    }
    public function save()
    {
        if ($this->multiple) {


            $this->dispatch('mediaSelected', field: $this->target, id: $this->selected);
        } else {

            $this->dispatch('mediaSelected', field: $this->target, id: $this->selected[0]);
        }
        $this->mediapickerModal = false;
    }
    public function removeSelect($id)
    {
        $this->selected = array_diff($this->selected, [$id]);
    }
    public function fileUploaded()
    {
        // Refresh the file list after a new file is uploaded
        $this->resetPage(); // Reset to the first page
    }
}
