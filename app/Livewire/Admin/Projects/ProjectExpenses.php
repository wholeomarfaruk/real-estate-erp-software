<?php

namespace App\Livewire\Admin\Projects;

use App\Models\BankingPaymentRequest;
use App\Models\FeatureAccountMapping;
use App\Models\Project;
use App\Models\Transaction;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectExpenses extends Component
{
    use WithPagination;

    public Project $project;
    public string $filterType   = '';
    public string $filterStatus = '';
    public string $search       = '';

    public function mount(Project $project)
    {
        if (!auth()->user()->can('project.view')) {
            abort(403);
        }
        $this->project = $project;
    }

    /**
     * Get enabled accounts for the project_expense feature.
     */
    private function getEnabledExpenseAccountIds(): array
    {
        return FeatureAccountMapping::where('feature_key', 'project_expense')
            ->where('is_enabled', true)
            ->pluck('child_account_id')
            ->toArray();
    }

    /**
     * Project expenses are transactions where enabled expense accounts have debit amounts
     * (amounts charged to the expense account).
     */
    private function baseQuery()
    {
        $enabledAccountIds = $this->getEnabledExpenseAccountIds();

        return Transaction::query()
            ->whereHas('lines', fn ($l) => $l->where('debit', '>', 0)
                ->whereIn('account_id', $enabledAccountIds))
            ->where('reference_type', Project::class)
            ->where('reference_id', $this->project->id);
    }

    /** Total debit from enabled expense accounts. */
    private function getExpenseAmount(Transaction $t): float
    {
        $enabledAccountIds = $this->getEnabledExpenseAccountIds();
        return (float) $t->lines
            ->whereIn('account_id', $enabledAccountIds)
            ->sum('debit');
    }

    private function getPhaseLabel(?string $phaseValue): string
    {
        if (!$phaseValue) {
            return 'Others';
        }

        try {
            $phase = \App\Enums\Projects\WorkPhase::from($phaseValue);
            return $phase->label();
        } catch (\ValueError) {
            return 'Others';
        }
    }

    public function render()
    {
        $expenses = $this->baseQuery()
            ->with(['lines.account:id,name'])
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('notes', 'like', '%' . $this->search . '%');
            }))
            ->latest('datetime')
            ->latest('id')
            ->paginate(15);

        $enabledAccountIds = $this->getEnabledExpenseAccountIds();

        // Map expenses to include phase info and expense amount
        $expenses->getCollection()->transform(function ($expense) use ($enabledAccountIds) {
            $expense->phase = $this->getPhaseLabel($expense->external_data['project_work_phase'] ?? null);
            $expense->expense_amount = (float) $expense->lines
                ->whereIn('account_id', $enabledAccountIds)
                ->sum('debit');
            return $expense;
        });

        $showEditButton = false;

        // KPIs from all posted expense transactions for this project
        $all = $this->baseQuery()->with('lines.account')->get();

        $totalAmount = (float) $all->sum(fn($t) => $this->getExpenseAmount($t));
        $thisMonth   = (float) $all->filter(fn($t) => $t->datetime?->isCurrentMonth())
            ->sum(fn($t) => $this->getExpenseAmount($t));

        // Pending (requested but not yet posted to ledger) — informational
        $pendingTotal = (float) BankingPaymentRequest::query()
            ->where('source_type', 'expense')
            ->where('sourceable_type', Project::class)
            ->where('sourceable_id', $this->project->id)
            ->whereIn('status', ['pending', 'approved', 'released'])
            ->sum('amount');

        $project = $this->project;
        return view('livewire.admin.projects.project-expenses', compact(
            'project', 'expenses', 'totalAmount', 'thisMonth',
            'pendingTotal', 'showEditButton'
        ))->layout('layouts.admin.admin');
    }
}
