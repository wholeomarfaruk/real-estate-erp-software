<?php

/*
|--------------------------------------------------------------------------
| Supplier Detail — routes (add to routes/web.php)
|--------------------------------------------------------------------------
| Four separate full-page Livewire components (one per tab / module), all
| bound to the same {supplier} via route-model binding. The tab nav inside
| <x-supplier.shell> links between them with wire:navigate (SPA-feel, the
| hero + KPI + tabs are re-rendered identically by each component).
|
| "View detail" on the supplier list points at suppliers.show.details.
*/

use App\Livewire\Suppliers\Show\Advances;
use App\Livewire\Suppliers\Show\Details;
use App\Livewire\Suppliers\Show\Invoices;
use App\Livewire\Suppliers\Show\Orders;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('admin/supplier')->group(function () {

    // List (from the supplier-handoff package) — name kept so the shell breadcrumb resolves
    // Route::get('suppliers', \App\Livewire\Suppliers\SupplierList::class)->name('suppliers.index');

    // Detail modules — {supplier} is route-model bound (App\Models\Supplier)
    Route::get('suppliers/{supplier}',           Details::class)->name('suppliers.show.details');
    Route::get('suppliers/{supplier}/invoices',  Invoices::class)->name('suppliers.show.invoices');
    Route::get('suppliers/{supplier}/orders',    Orders::class)->name('suppliers.show.orders');
    Route::get('suppliers/{supplier}/advances',  Advances::class)->name('suppliers.show.advances');
});

/*
| Wire the LIST "View detail" action (supplier-handoff/SupplierList.php) to open this page:
|
|   public function view(int $id)
|   {
|       return $this->redirectRoute('suppliers.show.details', $id);
|   }
|
| …and/or make each list row a link:
|   <a href="{{ route('suppliers.show.details', $s) }}" wire:navigate> … </a>
*/
