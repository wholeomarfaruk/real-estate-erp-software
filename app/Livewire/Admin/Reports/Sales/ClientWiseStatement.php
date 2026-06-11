<?php

namespace App\Livewire\Admin\Reports\Sales;

use App\Services\Reports\Sales\ClientWiseStatementService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ClientWiseStatement extends Component
{
    public ?int $customerId = null;

    public string $fromDate = '';

    public string $toDate = '';

    public string $transactionType = 'all';

    public string $preset = 'month';

    public function mount(?int $customer_id = null): void
    {
        $this->authorizePermission('reports.sales.client-wise-statement.view');

        if (!$customer_id) {
            abort(400, 'Customer ID is required.');
        }

        $this->customerId = $customer_id;

        $today = now()->toDateString();
        $this->fromDate = $this->fromDate ?: Carbon::now()->startOfMonth()->toDateString();
        $this->toDate = $this->toDate ?: $today;

        $this->syncPreset();
    }

    public function updated(string $name): void
    {
        if (in_array($name, ['fromDate', 'toDate'], true)) {
            $this->syncPreset();
        }
    }

    public function applyPreset(string $preset): void
    {
        $now = now();

        $this->preset = in_array($preset, ['today', 'month', 'year', 'custom'], true) ? $preset : 'today';

        if ($this->preset === 'month') {
            $this->fromDate = $now->copy()->startOfMonth()->toDateString();
            $this->toDate = $now->copy()->endOfMonth()->toDateString();

            return;
        }

        if ($this->preset === 'year') {
            $this->fromDate = $now->copy()->startOfYear()->toDateString();
            $this->toDate = $now->copy()->endOfYear()->toDateString();

            return;
        }

        if ($this->preset === 'custom') {
            return;
        }

        $this->fromDate = $now->toDateString();
        $this->toDate = $now->toDateString();
    }

    public function resetFilters(): void
    {
        $this->transactionType = 'all';

        $this->applyPreset('month');
    }

    public function render(ClientWiseStatementService $service): View
    {
        $this->authorizePermission('reports.sales.client-wise-statement.view');

        $report = $service->build($this->filterPayload());

        return view('livewire.admin.reports.sales.client-wise-statement', [
            'report' => $report,
            'transactionTypes' => $service->getTransactionTypes(),
            'printUrl' => route('admin.reports.sales.export.print', array_merge(['report' => 'client-wise-statement'], $this->exportQuery())),
            'pdfUrl' => route('admin.reports.sales.export.pdf', array_merge(['report' => 'client-wise-statement'], $this->exportQuery())),
            'excelUrl' => route('admin.reports.sales.export.excel', array_merge(['report' => 'client-wise-statement'], $this->exportQuery())),
        ])->layout('layouts.admin.admin');
    }

    protected function filterPayload(): array
    {
        return [
            'customer_id' => $this->customerId,
            'transaction_type' => $this->transactionType,
            'from_date' => $this->fromDate,
            'to_date' => $this->toDate,
        ];
    }

    protected function exportQuery(): array
    {
        return array_filter($this->filterPayload(), static fn (mixed $value): bool => $value !== null && $value !== '' && $value !== 'all');
    }

    protected function syncPreset(): void
    {
        try {
            $from = Carbon::parse($this->fromDate);
            $to = Carbon::parse($this->toDate);
        } catch (\Throwable) {
            $this->preset = 'custom';

            return;
        }

        if ($from->gt($to)) {
            $this->preset = 'custom';

            return;
        }

        if ($from->isSameDay($to) && $from->isToday()) {
            $this->preset = 'today';

            return;
        }

        if (
            $from->isSameMonth($to)
            && $from->copy()->startOfMonth()->isSameDay($from)
            && $to->copy()->endOfMonth()->isSameDay($to)
            && $from->isSameMonth(now())
        ) {
            $this->preset = 'month';

            return;
        }

        if (
            $from->year === $to->year
            && $from->copy()->startOfYear()->isSameDay($from)
            && $to->copy()->endOfYear()->isSameDay($to)
            && $from->year === (int) now()->format('Y')
        ) {
            $this->preset = 'year';

            return;
        }

        $this->preset = 'custom';
    }

    protected function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403, 'Unauthorized action.');
    }
}
