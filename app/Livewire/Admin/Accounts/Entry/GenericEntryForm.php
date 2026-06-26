<?php

namespace App\Livewire\Admin\Accounts\Entry;

use App\Models\AccountEntryType;
use Illuminate\Contracts\View\View;

class GenericEntryForm extends BaseEntryForm
{
    public AccountEntryType $entryType;

    public function mount(AccountEntryType $entryType): void
    {
        $this->entryType = $entryType;
        $this->date = now()->toDateString();
    }

    public function render(): View
    {
        return view('livewire.admin.accounts.entry.generic-entry-form', [
            'debitAccounts' => $this->entryType->resolveDebitAccounts(),
            'creditAccounts' => $this->entryType->resolveCreditAccounts(),
        ]);
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
            'phone' => $this->phone,
            'notes' => $this->notes,
        ];
    }
}
