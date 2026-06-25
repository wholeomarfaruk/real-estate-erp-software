<?php

namespace App\Livewire\Admin\Accounts\Expense;

use App\Enums\Accounts\FeatureType;
use App\Enums\Accounts\TransactionType;
use App\Livewire\Admin\Accounts\Concerns\InteractsWithAccountsAccess;
use App\Livewire\Admin\Accounts\Concerns\InteractsWithFeatureAccounts;
use App\Livewire\Traits\WithMediaPicker;
use App\Models\Account;
use App\Services\Accounts\RequestEngine;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class OfficeExpenseForm extends Component
{
    use InteractsWithAccountsAccess, InteractsWithFeatureAccounts, WithMediaPicker;

    public ?int   $expense_account_id  = null;
    public ?int   $payment_account_id  = null;
    public string $payment_method      = 'cash';
    public string $transaction_type    = 'expense';
    public string $title               = '';
    public string $date                = '';
    public string $amount              = '';
    public string $reference_no        = '';
    public string $paid_to_name        = '';
    public string $paid_to_phone       = '';
    public string $notes               = '';
    public array  $attachments         = [];

    public function mount(): void
    {
        $this->authorizePermission('accounts.expense.create');
        $this->date = now()->toDateString();
    }

    public function save(): void
    {
        try {
            $this->authorizePermission('accounts.expense.create');

            $rules = [
                'expense_account_id' => 'required|integer|exists:accounts,id',
                'payment_account_id' => 'required|integer|exists:accounts,id',
                'payment_method'     => 'required|string|in:cash,bank,cheque,mobile_banking',
                'title'              => 'required|string|max:200',
                'date'               => 'required|date',
                'amount'             => 'required|numeric|gt:0',
                'reference_no'       => 'nullable|string|max:100',
                'paid_to_name'       => 'nullable|string|max:200',
                'paid_to_phone'      => 'nullable|string|max:20',
                'notes'              => 'nullable|string|max:1000',
                'attachments'        => 'nullable|array',
                'attachments.*'      => 'integer|exists:files,id',
            ];

            $this->validate($rules);

            $normalizedAttachmentIds = $this->normalizedAttachmentIds();

            // Create expense request via RequestEngine
            $requestEngine = app(RequestEngine::class);
            $bpr = $requestEngine->createExpenseRequest(
                expenseType: 'office_expense',
                expenseAccountId: (int) $this->expense_account_id,
                paymentAccountId: (int) $this->payment_account_id,
                paymentMethod: $this->payment_method,
                amount: (float) $this->amount,
                title: $this->title,
                referenceNo: $this->reference_no,
                paidToName: $this->paid_to_name,
                paidToPhone: $this->paid_to_phone,
                attachmentIds: !empty($normalizedAttachmentIds) ? $normalizedAttachmentIds : null,
                userId: Auth::id()
            );

            $this->dispatch('toast', type: 'success', message: 'Office expense request created successfully. It is now pending approval.');
            $this->redirectRoute('admin.accounts.expenses.index', navigate: true);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('toast', type: 'error', message: $e->validator->errors()->first());
            throw $e;
        } catch (\Throwable $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    private function normalizedAttachmentIds(): array
    {
        return array_values(
            array_unique(
                array_filter(
                    array_map('intval', $this->attachments),
                    fn ($id) => $id > 0
                )
            )
        );
    }

    public function render(): View
    {
        $officeAccounts = $this->getAllEnabledChildrenForFeature(FeatureType::OFFICE_EXPENSE->value);
        if ($officeAccounts->isEmpty()) {
            $officeAccounts = Account::query()
                ->where('code', 'EXP-OFFICE')
                ->first()
                ?->children()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name'])
                ?? collect();
        }

        $paymentAccounts = Account::query()
            ->whereIn('type', ['cash', 'bank', 'mfs', 'wallet'])
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'type', 'name']);

        $paymentMethods = [
            'cash' => 'Cash',
            'bank' => 'Bank Transfer',
            'cheque' => 'Cheque',
            'mobile_banking' => 'Mobile Banking',
        ];

        return view('livewire.admin.accounts.expense.office-expense-form', [
            'officeAccounts'  => $officeAccounts,
            'paymentAccounts' => $paymentAccounts,
            'paymentMethods'  => $paymentMethods,
        ])->layout('layouts.admin.admin');
    }
}
