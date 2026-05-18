<?php

namespace App\Livewire\Admin\Accounts\Transaction;

use App\Enums\Accounts\TransactionType;
use App\Livewire\Admin\Accounts\Concerns\InteractsWithAccountsAccess;
use App\Models\File;
use App\Models\Transaction;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class TransactionList extends Component
{
    use InteractsWithAccountsAccess;
    use WithPagination;

    public string $search = '';

    public string $typeFilter = '';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public bool $showViewModal = false;

    public ?int $viewTransactionId = null;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('accounts.transaction.list');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function viewTransaction(int $id): void
    {
        $this->authorizePermission('accounts.transaction.view');

        $exists = Transaction::query()->whereKey($id)->exists();

        if (! $exists) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Transaction not found.']);

            return;
        }

        $this->viewTransactionId = $id;
        $this->showViewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->showViewModal = false;
        $this->viewTransactionId = null;
    }

    public function render(): View
    {
        $this->authorizePermission('accounts.transaction.list');

        $transactions = Transaction::query()
            ->with(['creator:id,name', 'account:id,name,code'])
            ->when($this->search !== '', function (Builder $query): void {
                $search = '%'.$this->search.'%';

                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery->where('notes', 'like', $search)
                        ->orWhere('name', 'like', $search)
                        ->orWhere('reference_type', 'like', $search)
                        ->orWhereRaw('CAST(reference_id as CHAR) like ?', [$search]);
                });
            })
            ->when($this->typeFilter !== '', fn (Builder $query): Builder => $query->where('type', $this->typeFilter))
            ->when($this->dateFrom, fn (Builder $query): Builder => $query->whereDate('datetime', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $query): Builder => $query->whereDate('datetime', '<=', $this->dateTo))
            ->latest('datetime')
            ->latest('id')
            ->paginate(15);

        $viewTransaction = null;

        if ($this->showViewModal && $this->viewTransactionId) {
            $viewTransaction = Transaction::query()
                ->with(['creator:id,name', 'account:id,name,code,type'])
                ->find($this->viewTransactionId);

            if (! $viewTransaction) {
                $this->showViewModal = false;
                $this->viewTransactionId = null;
            }
        }

        $viewTransactionFiles = $viewTransaction
            ? File::query()->whereIn('id', $viewTransaction->attachments ?? [])->get(['id', 'name', 'type', 'extension'])
            : collect();

        return view('livewire.admin.accounts.transaction.transaction-list', [
            'transactions' => $transactions,
            'types' => TransactionType::cases(),
            'viewTransaction' => $viewTransaction,
            'viewTransactionFiles' => $viewTransactionFiles,
        ])->layout('layouts.admin.admin');
    }
}
