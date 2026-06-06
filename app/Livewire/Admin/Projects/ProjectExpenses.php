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
     * IDs of this project's expense banking requests (the "request holders").
     * Posted ledger transactions reference these via reference_type/reference_id.
     */
    private function projectRequestIds()
    {
        return BankingPaymentRequest::query()
            ->where('source_type', 'expense')
            ->where('sourceable_type', Project::class)
            ->where('sourceable_id', $this->project->id)
            ->pluck('id');
    }

    /** Actual project expenses live in the transactions ledger (type=expense, DR side). */
    private function baseQuery($requestIds)
    {
        return Transaction::query()
            ->where('type', TransactionType::EXPENSE->value)
            ->where('debit', '>', 0)
            ->where('reference_type', 'banking_payment_request')
            ->whereIn('reference_id', $requestIds);
    }

    public function render()
    {
        $requestIds = $this->projectRequestIds();

        $expenses = $this->baseQuery($requestIds)
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

        // KPIs from all posted expense transactions for this project
        $all = $this->baseQuery($requestIds)->with('transactionCategory')->get();

        $totalAmount = (float) $all->sum('debit');
        $thisMonth   = (float) $all->filter(fn($t) => $t->datetime?->isCurrentMonth())->sum('debit');

        $labourTotal = (float) $all->filter(fn($t) =>
            str_contains(strtolower($t->transactionCategory?->name ?? ''), 'labour') ||
            str_contains(strtolower($t->transactionCategory?->name ?? ''), 'labor')
        )->sum('debit');
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
            ->map(fn($g) => (float) $g->sum('debit'));

        $categories = TransactionCategory::active()
            ->where('type', 'expense')
            ->get();

        $project = $this->project;
        return view('livewire.admin.projects.project-expenses', compact(
            'project', 'expenses', 'totalAmount', 'thisMonth',
            'labourTotal', 'otherTotal', 'pendingTotal', 'categories', 'actualByCategory'
        ))->layout('layouts.admin.admin');
    }
}
