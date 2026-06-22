<?php

namespace App\Livewire\Admin\Accounts\Expense;

use App\Enums\Accounts\TransactionType;
use App\Livewire\Admin\Accounts\Concerns\InteractsWithAccountsAccess;
use App\Livewire\Traits\WithMediaPicker;
use App\Models\Account;
use App\Models\BankingPaymentRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class OfficeExpenseForm extends Component
{
    use InteractsWithAccountsAccess, WithMediaPicker;

    public ?int   $expense_account_id  = null;
    public ?int   $payment_account_id  = null;
    public string $payment_method      = 'cash';
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

            $externalData = [
                'expense_account_id' => $this->expense_account_id,
                'payment_account_id' => $this->payment_account_id,
                'payment_method'     => $this->payment_method,
                'reference_no'       => $this->reference_no ?: null,
                'paid_to_name'       => $this->paid_to_name ?: null,
                'paid_to_phone'      => $this->paid_to_phone ?: null,
            ];

            if (! empty($normalizedAttachmentIds)) {
                $externalData['attachments'] = $normalizedAttachmentIds;
            }

            $bpr = BankingPaymentRequest::create([
                'request_no'              => BankingPaymentRequest::generateRequestNo(),
                'source_type'             => TransactionType::EXPENSE->value,
                'transaction_category_id' => null,
                'bank_account_id'         => null,
                'amount'                  => round((float) $this->amount, 3),
                'description'             => $this->title,
                'status'                  => 'pending',
                'notes'                   => $this->notes ?: null,
                'requested_by'            => Auth::id(),
                'external_data'           => $externalData,
            ]);

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
        $officeAccounts = Account::query()
            ->where('code', 'EXP-OFFICE')
            ->first()
            ?->children()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name'])
            ?? collect();

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
