<?php
/*
|--------------------------------------------------------------------------
| Reports — routes (add to routes/web.php)
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Admin\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {

    Route::controller(ReportController::class)->prefix('reports')->name('reports.')->group(function () {

        Route::get('/',                'index')     ->name('index');        // hub
        Route::get('/dashboard',       'dashboard') ->name('dashboard');    // stub
        Route::get('/builder',         'builder')   ->name('builder');      // stub
        Route::get('/scheduled',       'scheduled') ->name('scheduled');    // stub
        Route::get('/{category}',      'category')  ->name('category');     // detail

    });

});

/*
|--------------------------------------------------------------------------
| Sidebar dropdown — add this snippet to your existing sidebar partial.
| Highlight the active category using the current route name.
|
| x-data="{ open: request()->routeIs('admin.reports.*') }"
|
| Example sub-item (repeat for each category key):
|
|  <a href="{{ route('admin.reports.category','finance') }}" wire:navigate
|     class="{{ request()->route('category')==='finance' ? 'active' : '' }}">
|    Finance
|  </a>
|--------------------------------------------------------------------------
*/
