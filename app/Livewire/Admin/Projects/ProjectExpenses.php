<?php

namespace App\Livewire\Admin\Projects;

use App\Enums\Accounts\TransactionType;
use App\Models\BankingPaymentRequest;
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
     * Actual project expenses live in the transactions ledger as a single
     * expense entry (type=expense) that references the Project directly via
     * reference_type/reference_id, with the amount recorded on the credit side
     * (money leaving the account).
     */
    private function baseQuery()
    {
        return Transaction::query()
            ->where('type', TransactionType::EXPENSE->value)
            ->whereHas('lines', fn ($l) => $l->where('credit', '>', 0))
            ->where('reference_type', Project::class)
            ->where('reference_id', $this->project->id);
    }

    /** Total credit (money out) across a transaction's ledger lines. */
    private function lineCredit(Transaction $t): float
    {
        return (float) $t->lines->sum('credit');
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

        // Map expenses to include phase info
        $expenses->getCollection()->transform(function ($expense) {
            $expense->phase = $this->getPhaseLabel($expense->external_data['project_work_phase'] ?? null);
            return $expense;
        });

        $showEditButton = false;

        // KPIs from all posted expense transactions for this project
        $all = $this->baseQuery()->with('lines.account')->get();

        $totalAmount = (float) $all->sum(fn($t) => $this->lineCredit($t));
        $thisMonth   = (float) $all->filter(fn($t) => $t->datetime?->isCurrentMonth())
            ->sum(fn($t) => $this->lineCredit($t));

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
