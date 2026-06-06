<?php

namespace App\Livewire\Admin\Projects;

use App\Enums\Accounts\TransactionType;
use App\Models\BankingPaymentRequest;
use App\Models\Project;
use App\Models\Transaction;
use App\Models\TransactionCategory;
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
     * Actual project expenses live in the transactions ledger as a single
     * expense entry (type=expense) that references the Project directly via
     * reference_type/reference_id, with the amount recorded on the credit side
     * (money leaving the account).
     */
    private function baseQuery()
    {
        return Transaction::query()
            ->where('type', TransactionType::EXPENSE->value)
            ->where('credit', '>', 0)
            ->where('reference_type', Project::class)
            ->where('reference_id', $this->project->id);
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
            ->with(['transactionCategory:id,name', 'account:id,name'])
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('notes', 'like', '%' . $this->search . '%');
            }))
            ->when($this->filterType, fn($q) => $q->whereHas('transactionCategory', fn($cq) =>
                $cq->where('name', 'like', '%' . $this->filterType . '%')
            ))
            ->latest('datetime')
            ->latest('id')
            ->paginate(15);

        // Map expenses to include phase info
        $expenses->getCollection()->transform(function ($expense) {
            $expense->phase = $this->getPhaseLabel($expense->external_data['project_work_phase'] ?? null);
            return $expense;
        });

        $showEditButton = true;

        // KPIs from all posted expense transactions for this project
        $all = $this->baseQuery()->with('transactionCategory')->get();

        $totalAmount = (float) $all->sum('credit');
        $thisMonth   = (float) $all->filter(fn($t) => $t->datetime?->isCurrentMonth())->sum('credit');

        $labourTotal = (float) $all->filter(fn($t) =>
            str_contains(strtolower($t->transactionCategory?->name ?? ''), 'labour') ||
            str_contains(strtolower($t->transactionCategory?->name ?? ''), 'labor')
        )->sum('credit');
        $otherTotal = $totalAmount - $labourTotal;

        // Pending (requested but not yet posted to ledger) — informational
        $pendingTotal = (float) BankingPaymentRequest::query()
            ->where('source_type', 'expense')
            ->where('sourceable_type', Project::class)
            ->where('sourceable_id', $this->project->id)
            ->whereIn('status', ['pending', 'approved', 'released'])
            ->sum('amount');

        // Estimate vs actual — actual per category from posted transactions
        $actualByCategory = $all->groupBy('transaction_category_id')
            ->map(fn($g) => (float) $g->sum('credit'));

        $categories = TransactionCategory::active()
            ->where('type', 'expense')
            ->get();

        $project = $this->project;
        return view('livewire.admin.projects.project-expenses', compact(
            'project', 'expenses', 'totalAmount', 'thisMonth',
            'labourTotal', 'otherTotal', 'pendingTotal', 'categories', 'actualByCategory', 'showEditButton'
        ))->layout('layouts.admin.admin');
    }
}
