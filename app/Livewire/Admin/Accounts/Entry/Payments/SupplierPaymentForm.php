<?php

namespace App\Livewire\Admin\Accounts\Entry\Payments;

use App\Livewire\Admin\Accounts\Entry\BaseEntryForm;
use App\Models\Account;
use Illuminate\Contracts\View\View;

class SupplierPaymentForm extends BaseEntryForm
{
    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    protected function entrySlug(): string
    {
        return 'supplier-payment';
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
            'description' => 'Supplier Payment',
        ];
    }

    public function render(): View
    {
        $payableAccounts = Account::where('group', 'liability')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $paymentAccounts = Account::whereIn('type', ['cash', 'bank', 'mfs', 'wallet'])
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'type']);

        return view('livewire.admin.accounts.entry.payments.supplier-payment-form', [
            'payableAccounts' => $payableAccounts,
            'paymentAccounts' => $paymentAccounts,
        ]);
    }
}
