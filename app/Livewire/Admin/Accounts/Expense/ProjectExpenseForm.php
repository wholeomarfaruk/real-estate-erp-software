<?php

namespace App\Livewire\Admin\Accounts\Expense;

use App\Enums\Accounts\FeatureType;
use App\Enums\Accounts\TransactionType;
use App\Enums\Projects\WorkPhase;
use App\Livewire\Admin\Accounts\Concerns\InteractsWithAccountsAccess;
use App\Livewire\Admin\Accounts\Concerns\InteractsWithFeatureAccounts;
use App\Livewire\Traits\WithMediaPicker;
use App\Models\Account;
use App\Models\BankingPaymentRequest;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProjectExpenseForm extends Component
{
    use InteractsWithAccountsAccess, InteractsWithFeatureAccounts, WithMediaPicker;

    public ?int   $project_id           = null;
    public ?int   $expense_account_id   = null;
    public ?int   $payment_account_id   = null;
    public string $payment_method       = 'cash';
    public string $title                = '';
    public string $date                 = '';
    public string $amount               = '';
    public string $reference_no         = '';
    public string $paid_to_name         = '';
    public string $paid_to_phone        = '';
    public string $notes                = '';
    public string $project_work_phase   = '';
    public array  $attachments          = [];

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
                'project_id'         => 'required|integer|exists:projects,id',
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
                'project_id'         => $this->project_id,
                'expense_account_id' => $this->expense_account_id,
                'payment_account_id' => $this->payment_account_id,
                'payment_method'     => $this->payment_method,
                'reference_no'       => $this->reference_no ?: null,
                'paid_to_name'       => $this->paid_to_name ?: null,
                'paid_to_phone'      => $this->paid_to_phone ?: null,
                'project_work_phase' => $this->project_work_phase ?: null,
            ];

            if (! empty($normalizedAttachmentIds)) {
                $externalData['attachments'] = $normalizedAttachmentIds;
            }

            // Create banking payment request
            $bpr = BankingPaymentRequest::create([
                'request_no'              => BankingPaymentRequest::generateRequestNo(),
                'source_type'             => TransactionType::EXPENSE->value,
                'sourceable_type'         => Project::class,
                'sourceable_id'           => $this->project_id,
                'transaction_category_id' => null,
                'amount'                  => round((float) $this->amount, 3),
                'description'             => $this->title,
                'notes'                   => $this->notes ?: null,
                'requested_by'            => Auth::id(),
                'external_data'           => $externalData,
            ]);

            session()->flash('success', "Project expense request #{$bpr->request_no} created successfully!");
            $this->redirect(route('admin.accounts.expenses.index'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->setErrorBag($e->validator->errors());
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create expense: ' . $e->getMessage());
        }
    }

    public function render(): View
    {
        $projects = Project::orderBy('name')->get(['id', 'name']);

        $expenseAccounts = $this->getAllEnabledChildrenForFeature(FeatureType::PROJECT_EXPENSE->value);

        $paymentAccounts = Account::where('type', 'cash')
            ->orWhere('type', 'bank')
            ->orWhere('type', 'mfs')
            ->orWhere('type', 'wallet')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $paymentMethods = [
            'cash'           => 'Cash',
            'bank'           => 'Bank Transfer',
            'cheque'         => 'Cheque',
            'mobile_banking' => 'Mobile Banking',
        ];

        $workPhases = WorkPhase::options();

        return view('livewire.admin.accounts.expense.project-expense-form', compact(
            'projects', 'expenseAccounts', 'paymentAccounts', 'paymentMethods', 'workPhases'
        ))->layout('layouts.admin.admin');
    }
}
