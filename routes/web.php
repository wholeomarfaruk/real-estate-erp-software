<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'))->name('home');
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return redirect()->route('admin.dashboard');
    })->name('dashboard');
});
Route::get('/projects/properties', function () {
    return "test";
})->name('projects.properties');
