<?php

namespace App\Livewire\Admin\Projects;

use Livewire\Component;
use App\Models\Project;
use App\Models\TimelinePhase;
use App\Models\TimelineTask;

class ProjectCalendar extends Component
{
    public $selectedProject;
    public $projects = [];
    public $events = [];

    public function mount()
    {
        if (!auth()->user()->can('project.view')) {
            abort(403, 'Unauthorized action.');
        }

        $this->projects = Project::orderBy('name')->get();
    }

    public function updatedSelectedProject()
    {
        $this->loadEvents();
    }

    public function loadEvents()
    {
        if (!$this->selectedProject) {
            $this->events = [];
            return;
        }

        $project = Project::with(['timelinePhases.tasks'])->find($this->selectedProject);
        if (!$project) {
            $this->events = [];
            return;
        }

        $events = [];

        // Add project dates
        $events[] = [
            'title' => 'Project: ' . $project->name,
            'start' => $project->start_date->format('Y-m-d'),
            'end' => $project->end_date->format('Y-m-d'),
            'color' => '#3B82F6',
            'type' => 'project'
        ];

        // Add phases
        foreach ($project->timelinePhases as $phase) {
            $events[] = [
                'title' => 'Phase: ' . $phase->name,
                'start' => $phase->start_date->format('Y-m-d'),
                'end' => $phase->end_date->format('Y-m-d'),
                'color' => '#10B981',
                'type' => 'phase',
                'progress' => $phase->progress_percentage
            ];

            // Add tasks
            foreach ($phase->tasks as $task) {
                $events[] = [
                    'title' => 'Task: ' . $task->name,
                    'start' => $task->start_date->format('Y-m-d'),
                    'end' => $task->end_date->format('Y-m-d'),
                    'color' => '#F59E0B',
                    'type' => 'task',
                    'status' => $task->status,
                    'progress' => $task->progress_percentage
                ];
            }
        }

        $this->events = $events;
    }

    public function render()
    {
        return view('livewire.admin.projects.project-calendar')
            ->layout('layouts.admin.admin');
    }
}