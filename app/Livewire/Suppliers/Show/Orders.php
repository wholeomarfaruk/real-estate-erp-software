<?php

namespace App\Livewire\Suppliers\Show;

use App\Models\Supplier;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Orders extends Component
{
    use WithPagination;

    public Supplier $supplier;

    public function mount(Supplier $supplier): void
    {
        $this->supplier = $supplier;
    }

    #[Computed]
    public function orders()
    {
        return $this->supplier->purchaseOrders()
            ->with(['store:id,name', 'requester:id,name'])
            ->withCount('items')
            ->latest('order_date')
            ->latest('id')
            ->paginate(15);
    }

    #[Computed]
    public function stats(): array
    {
        $rows = $this->supplier->purchaseOrders()
            ->selectRaw("COUNT(*) as total, SUM(actual_purchase_amount) as order_value,
                SUM(CASE WHEN status IN ('approved','partially_received') THEN 1 ELSE 0 END) as open_count,
                SUM(CASE WHEN status = 'received' THEN 1 ELSE 0 END) as received_count")
            ->first();

        return [
            'total'          => $rows->total ?? 0,
            'order_value'    => $rows->order_value ?? 0,
            'open_count'     => $rows->open_count ?? 0,
            'received_count' => $rows->received_count ?? 0,
        ];
    }

    public function view(int $id, string $poNo): void
    {
        $this->redirect(route('admin.inventory.purchase-orders.view', $id));
    }

    public function downloadPdf(int $id, string $poNo): void
    {
        $this->redirect(route('admin.inventory.purchase-orders.pdf', $id));
    }

    public function render()
    {
        return view('livewire.suppliers.show.orders')->layout('layouts.admin.admin');
    }
}
