<?php

namespace App\Livewire\Admin\Accounts\Entry\Payments;

use App\Livewire\Admin\Accounts\Entry\BaseEntryForm;
use App\Models\Account;
use Illuminate\Contracts\View\View;

class OwnerWithdrawalForm extends BaseEntryForm
{
    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    protected function entrySlug(): string
    {
        return 'owner-withdrawal';
    }

    protected function extraRules(): array
    {
        return [];
    }

    protected function buildPayload(): array
    {
        return [
            'debit_account_id' => $this->debit_account_id,
            'credit_account_id' => $this->credit_account_id,
            'amount' => $this->amount,
            'date' => $this->date,
            'method' => $this->method,
            'reference_no' => $this->reference_no,
            'name' => $this->name,
            'notes' => $this->notes,
            'description' => 'Owner Withdrawal',
        ];
    }

    public function render(): View
    {
        $equityAccounts = Account::where('group', 'equity')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $paymentAccounts = Account::whereIn('type', ['cash', 'bank', 'mfs', 'wallet'])
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'type']);

        return view('livewire.admin.accounts.entry.payments.owner-withdrawal-form', [
            'equityAccounts' => $equityAccounts,
            'paymentAccounts' => $paymentAccounts,
        ]);
    }
}
