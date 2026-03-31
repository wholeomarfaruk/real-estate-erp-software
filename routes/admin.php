<?php

use App\Http\Controllers\Admin\FileUploadController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', \App\Livewire\Admin\Dashboard\Dashboard::class)->name('dashboard');

//user managements
Route::get('/users', App\Livewire\Admin\Users\Users::class)->name('users');

// Profile and Settings
Route::get('/profile', App\Livewire\Admin\Profile\Profile::class)->name('profile');
Route::get('/settings', App\Livewire\Admin\Settings\Settings::class)->name('settings');

//permissions
Route::get('/permissions/roles', App\Livewire\Admin\Permissions\RoleList::class)->name('roles.list');
Route::get('/permissions/role/create', App\Livewire\Admin\Permissions\RoleCreate::class)->name('roles.create');
Route::get('/permissions/role/edit/{id}', App\Livewire\Admin\Permissions\RoleEdit::class)->name('roles.edit');

//projects
Route::get('/projects', App\Livewire\Admin\Projects\ProjectList::class)->name('projects.list');
Route::get('/projects/create', App\Livewire\Admin\Projects\ProjectCreate::class)->name('projects.create');
Route::get('/projects/{project}', App\Livewire\Admin\Projects\ProjectDetails::class)->name('projects.details');

//floors
Route::get('/floors', App\Livewire\Admin\Projects\FloorList::class)->name('floors.list');

//units
Route::get('/units', App\Livewire\Admin\Projects\UnitList::class)->name('units.list');

//project calendar
Route::get('/project-calendar', App\Livewire\Admin\Projects\ProjectCalendar::class)->name('project.calendar');

//uploads
Route::get('/uploads', App\Livewire\Admin\File\Uploads::class)->name('uploads');
Route::post('/upload', [FileUploadController::class, 'storeAdmin']);
Route::delete('/upload/revert', [FileUploadController::class, 'revertAdmin']);
