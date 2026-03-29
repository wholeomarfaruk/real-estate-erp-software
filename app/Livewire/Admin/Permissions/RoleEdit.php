<?php

namespace App\Livewire\Admin\Permissions;


use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleEdit extends Component
{
    public $permissionsGrouped;
    public $name;
    public $permissions=[];
    public $allPermissions;
    public $selectAll = false;
    public $role;
    public function mount($id)
    {

        if(!auth()->user()->can('role.edit')) {
            return abort(403, 'Unauthorized action.');
        }
        $role = Role::find($id);
        if($role){
            $this->name = $role->name;
            $this->permissions = $role->permissions()->pluck('name')->toArray();
        }
        $this->role = $role;
        $permissions = Permission::all();

        $this->allPermissions = $permissions;
        $grouped = collect($permissions)
            ->map(function ($item) {
                $item->group_name = explode('.', $item->name)[0]; // add new field
                return $item;
            })
            ->groupBy('group_name');
        $this->permissionsGrouped = $grouped;
        // dd($this->permissionsGrouped);
    }
    public function updatedSelectAll()
    {
        if($this->selectAll){
            $this->permissions = $this->allPermissions->pluck('name')->toArray();
        }
        if(!$this->selectAll){
            $this->permissions = [];
        }
    }
    public function render()
    {
        return view('livewire.admin.permissions.role-edit')->layout('layouts.admin.admin');
    }
    public function save()
    {

        $this->validate([
        'name' => 'required|unique:roles,name,' . $this->role->id,
        ]);
        // dd($this->permissions);
        $role = $this->role;
        $role->name = $this->name;
        $role->syncPermissions($this->permissions);
        $role->save();
        return redirect()->route('admin.roles.list')->with('success', 'Role & Permissions updated successfully.');
    }
}
