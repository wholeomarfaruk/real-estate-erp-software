<?php

namespace App\Livewire\Admin\Projects;

use Livewire\Component;
use App\Models\Unit;
use Illuminate\Contracts\View\View;

class UnitView extends Component
{
    public Unit $unit;

    public function mount(Unit $unit): void
    {
        if (! auth()->user()?->can('project.view')) {
            abort(403, 'Unauthorized action.');
        }

        $this->unit = $unit;
    }

    public function render(): View
    {
        return view('livewire.admin.projects.unit-view', ['unit' => $this->unit])->layout('layouts.admin.admin');
    }
}
