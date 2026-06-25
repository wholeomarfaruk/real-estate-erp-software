<?php

namespace App\Livewire\Admin\Accounts\Expense;

use App\Enums\Accounts\FeatureType;
use App\Enums\Accounts\TransactionType;
use App\Enums\Projects\WorkPhase;
use App\Livewire\Admin\Accounts\Concerns\InteractsWithAccountsAccess;
use App\Livewire\Admin\Accounts\Concerns\InteractsWithFeatureAccounts;
use App\Livewire\Traits\WithMediaPicker;
use App\Models\Account;
use App\Models\Project;
use App\Services\Accounts\RequestEngine;
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
    public string $transaction_type     = 'expense';
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

    /**
     * Extract attachment IDs from the attachments array.
     */
    private function normalizedAttachmentIds(): array
    {
        return array_map(function ($item) {
            if (is_array($item)) {
                return $item['id'] ?? $item;
            }
            return (int) $item;
        }, $this->attachments);
    }

    public function save(): void
    {
        try {
            \Log::info('ProjectExpenseForm::save() called', ['project_id' => $this->project_id]);

            $this->authorizePermission('accounts.expense.create');

            $rules = [
                'project_id'         => 'required|integer|exists:projects,id',
                'expense_account_id' => 'required|integer|exists:accounts,id',
                'payment_account_id' => 'required|integer|exists:accounts,id',
                'payment_method'     => 'required|string|in:cash,bank,cheque,mobile_banking',
                'transaction_type'   => 'required|string|in:' . implode(',', TransactionType::payments()),
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

            \Log::info('Validating form', $rules);
            $this->validate($rules);

            $normalizedAttachmentIds = $this->normalizedAttachmentIds();

            // Create expense request via RequestEngine
            $requestEngine = app(RequestEngine::class);
            $bpr = $requestEngine->createProjectExpenseRequest(
                projectId: (int) $this->project_id,
                expenseAccountId: (int) $this->expense_account_id,
                paymentAccountId: (int) $this->payment_account_id,
                paymentMethod: $this->payment_method,
                amount: (float) $this->amount,
                title: $this->title,
                date: $this->date,
                referenceNo: $this->reference_no,
                paidToName: $this->paid_to_name,
                paidToPhone: $this->paid_to_phone,
                workPhase: $this->project_work_phase,
                transactionType: $this->transaction_type,
                attachmentIds: !empty($normalizedAttachmentIds) ? $normalizedAttachmentIds : null,
                userId: Auth::id()
            );

            session()->flash('success', "Project expense request #{$bpr->request_no} created successfully!");
            $this->redirect(route('admin.accounts.expenses.index'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('ProjectExpenseForm validation failed', ['errors' => $e->validator->errors()->all()]);
            $this->setErrorBag($e->validator->errors());
            $errors = $e->validator->errors()->all();
            $errorMsg = count($errors) === 1 ? $errors[0] : implode(', ', $errors);
            $this->dispatch('notify', type: 'error', message: 'Validation Error: ' . $errorMsg);
        } catch (\Exception $e) {
            \Log::error('ProjectExpenseForm save failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            session()->flash('error', 'Failed to create expense: ' . $e->getMessage());
            $this->dispatch('notify', type: 'error', message: 'Failed to create expense: ' . $e->getMessage());
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

        // Get PAYMENT group transaction types
        $transactionTypes = collect(TransactionType::cases())
            ->filter(fn($type) => $type->reportGroup()->value === 'payment')
            ->mapWithKeys(fn($type) => [$type->value => $type->label()])
            ->toArray();

        return view('livewire.admin.accounts.expense.project-expense-form', compact(
            'projects', 'expenseAccounts', 'paymentAccounts', 'paymentMethods', 'workPhases', 'transactionTypes'
        ))->layout('layouts.admin.admin');
    }
}
