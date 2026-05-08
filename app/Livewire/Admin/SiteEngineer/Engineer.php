<?php

namespace App\Livewire\Admin\SiteEngineer;

use App\Models\Panel;
use App\Models\Project;
use App\Models\User;
use Livewire\Component;

class Engineer extends Component
{
    public $search = '';
    protected $queryString = ['search'];

    public $user;
    public $viewModal = false;
    public $projectIds;
    public $panelId;
    public function updatingSearch()
    {
        $this->resetPage();
    }
    public $engineer_id;
    public function render()
    {
        $users = User::query()
        ->whereHas('roles', function ($query) {
            $query->where('name', 'engineer');
        })
        ->where(function ($query) {
            $query->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('email', 'like', '%' . $this->search . '%');
        })
        ->get();
        $projects = Project::all();
        $panels = Panel::all();

        return view('livewire.admin.site-engineer.engineer', compact('users','projects','panels'))->layout('layouts.admin.admin');
    }
    public function updatedPanelId($panel_id)
    {

 
        $user = $this->user;
        if($panel_id == null) {
            $user->panels()->detach();
            return $this->dispatch('toast', [
                 'type' => 'success',
            'message' => 'User Panel removed successfully'
            ]);
        }
        $user->panels()->sync($panel_id);
        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'User Panel updated successfully'
        ]);

    }
        public function viewUser($id)
    {
        $user = User::find($id);
        if (!$user) {
            return abort(404);
        }
        $this->user = $user;
       
        $this->viewModal = true;
    }
    public function updatedProjectIds($projectIds)
    {

    $user = $this->user;

        if($projectIds == null) {
            $user->projects()->detach();
            return $this->dispatch('toast', [
                 'type' => 'success',
            'message' => 'User Projects removed successfully'
            ]);
        }
        $user->projects()->sync($projectIds);
        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'User Projects updated successfully'
        ]);
    }
}
