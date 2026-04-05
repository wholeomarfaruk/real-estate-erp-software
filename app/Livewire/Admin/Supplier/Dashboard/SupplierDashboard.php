<?php

namespace App\Livewire\Admin\Supplier\Dashboard;

use App\Livewire\Admin\Supplier\Concerns\InteractsWithSupplierAccess;
use App\Models\SupplierBill;
use App\Services\Supplier\SupplierDashboardService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class SupplierDashboard extends Component
{
    use InteractsWithSupplierAccess;

    public function mount(): void
    {
        $this->authorizePermission('supplier.dashboard.view');
        SupplierBill::syncOverdueStatuses();
    }

    public function render(): View
    {
        $this->authorizePermission('supplier.dashboard.view');
        SupplierBill::syncOverdueStatuses();

        $service = app(SupplierDashboardService::class);

        return view('livewire.admin.supplier.dashboard.supplier-dashboard', [
            'summary' => $service->summaryCards(),
            'alerts' => $service->alertWidgets(),
            'charts' => $service->chartData(),
            'activity' => $service->recentActivity(),
        ])->layout('layouts.admin.admin');
    }
}
