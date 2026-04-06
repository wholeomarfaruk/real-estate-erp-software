<?php

namespace App\Livewire\Admin\Projects;

use Livewire\Component;
use App\Models\Floor;
use Illuminate\Contracts\View\View;

class FloorView extends Component
{
    public Floor $floor;

    public function mount(Floor $floor): void
    {
        if (! auth()->user()?->can('project.view')) {
            abort(403, 'Unauthorized action.');
        }

        $this->floor = $floor;
    }

    public function render(): View
    {
        return view('livewire.admin.projects.floor-view', ['floor' => $this->floor])->layout('layouts.admin.admin');
    }
}
