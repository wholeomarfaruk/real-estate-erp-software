<?php

namespace App\Livewire\Admin\Accounts\Entry\Receipts;

use App\Livewire\Admin\Accounts\Entry\BaseEntryForm;
use App\Models\Account;
use Illuminate\Contracts\View\View;

class IncomeForm extends BaseEntryForm
{
    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    protected function entrySlug(): string
    {
        return 'income';
    }

    protected function extraRules(): array
    {
        return [
            'name' => 'nullable|string|max:200',
            'phone' => 'nullable|string|max:20',
        ];
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
            'description' => 'Income Entry',
        ];
    }

    public function render(): View
    {
        $paymentAccounts = Account::whereIn('type', ['cash', 'bank', 'mfs', 'wallet'])
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'type']);

        $incomeAccounts = Account::where('group', 'income')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return view('livewire.admin.accounts.entry.receipts.income-form', [
            'paymentAccounts' => $paymentAccounts,
            'incomeAccounts' => $incomeAccounts,
        ]);
    }
}
