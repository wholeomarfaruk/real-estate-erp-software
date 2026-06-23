<?php

namespace App\Livewire\Admin\Reports\Finance;

use App\Services\Reports\Finance\DailyStatementService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class DailyStatement extends Component
{
    public string $selectedDate = '';

    public function mount(): void
    {
        $this->authorizePermission('reports.finance.daily-statement.view');
        $this->selectedDate = now()->toDateString();
    }

    public function updated(string $name): void
    {
        if ($name === 'selectedDate') {
            // Livewire reactivity - auto-refresh on date change
        }
    }

    public function previousDay(): void
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)
            ->subDay()
            ->toDateString();
    }

    public function nextDay(): void
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)
            ->addDay()
            ->toDateString();
    }

    public function render(DailyStatementService $service): View
    {
        $this->authorizePermission('reports.finance.daily-statement.view');

        $report = $service->build(['date' => $this->selectedDate]);

        return view('livewire.admin.reports.finance.daily-statement', [
            'report' => $report,
            'selectedDate' => $this->selectedDate,
            'pdfUrl' => route('admin.reports.finance.daily-statement.pdf', ['date' => $this->selectedDate]),
            'excelUrl' => route('admin.reports.finance.daily-statement.excel', ['date' => $this->selectedDate]),
            'printUrl' => route('admin.reports.finance.daily-statement.print', ['date' => $this->selectedDate]),
        ])->layout('layouts.admin.admin');
    }

    protected function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403, 'Unauthorized action.');
    }
}
