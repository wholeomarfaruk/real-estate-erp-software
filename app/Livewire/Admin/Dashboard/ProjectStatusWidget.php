<?php

namespace App\Livewire\Admin\Dashboard;

use App\Models\Project;
use Livewire\Component;

class ProjectStatusWidget extends Component
{
    public $activeProjects = 0;
    public $completedProjects = 0;

    public function mount()
    {
        $this->calculateProjectStatus();
    }

    public function calculateProjectStatus()
    {
        try {
            if (class_exists(Project::class)) {
                $this->activeProjects = Project::query()
                    ->where('status', '!=', 'completed')
                    ->count() ?? 0;
                $this->completedProjects = Project::query()
                    ->where('status', 'completed')
                    ->count() ?? 0;
            }
        } catch (\Exception $e) {
            \Log::error('Error calculating project status: ' . $e->getMessage());
            $this->activeProjects = 0;
            $this->completedProjects = 0;
        }
    }

    public function render()
    {
        return view('livewire.admin.dashboard.project-status');
    }
}
