<?php

namespace App\Livewire\Admin\Ui\Layouts;

use Livewire\Component;

class Layouts extends Component
{
    public $pages =[];
    public $search = '';
    public function updateSearch(){
       $this->resetPage();
    }
    public function mount(){
        $pages = [
            ['name' => 'Blank Page', 'route' => 'ui.layouts.blank-page', 'slug' => 'blank-page', 'description' => 'A blank page layout for admin panel.'],
        ];
        $this->pages = $pages;
    }
    public function render()
    {
            $filteredPages = collect($this->pages)->filter(function ($page) {
                return str_contains(strtolower($page['name']), strtolower($this->search)) ||
                    str_contains(strtolower($page['description']), strtolower($this->search));
            })->values()->all();
        return view('livewire.admin.ui.layouts.layouts', compact('filteredPages'))
            ->layout('layouts.admin.admin');
    }
}
