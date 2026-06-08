<?php

namespace App\Livewire\Admin\Accounts\Expense;

use App\Enums\Accounts\TransactionType;
use App\Enums\Projects\WorkPhase;
use App\Livewire\Admin\Accounts\Concerns\InteractsWithAccountsAccess;
use App\Livewire\Traits\WithMediaPicker;
use App\Models\BankAccount;
use App\Models\BankingPaymentRequest;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\TransactionCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ExpenseForm extends Component
{
    use InteractsWithAccountsAccess, WithMediaPicker;

    // ─── Form fields ─────────────────────────────────────────────────────────
    public ?int   $parent_category_id      = null;   // active tab (parent expense category)
    public ?int   $transaction_category_id = null;   // chosen category (manual dropdown)
    public ?int   $bank_account_id         = null;
    public string $title                   = '';
    public string $date                    = '';
    public string $amount                  = '';
    public string $notes                   = '';
    public ?int   $reference_id            = null;    // project or supplier id
    public ?string $project_work_phase     = null;    // optional work phase for project expenses
    public array  $attachments             = [];      // selected file IDs

    public function mount(): void
    {
        $this->authorizePermission('accounts.expense.create');
        $this->date = now()->toDateString();

        // Default to the first parent expense category tab.
        $first = $this->parentCategories()->first();
        if ($first) {
            $this->selectTab($first->id);
        }

        // If project_id is passed in query params, find the project expense tab and select it
        $projectId = request()->query('project_id');
        if ($projectId) {
            $projectTab = TransactionCategory::query()
                ->where('type', 'expense')
                ->where('slug', 'project-expense')
                ->first();

            if ($projectTab) {
                $this->selectTab($projectTab->id);
                $this->reference_id = (int) $projectId;
            }
        }
    }

    /** Parent expense categories — the dynamic tabs. */
    private function parentCategories()
    {
        $root = TransactionCategory::query()
            ->where('type', 'expense')
            ->whereNull('parent_id')
            ->first();

        if (! $root) {
            return collect();
        }

        return TransactionCategory::query()
            ->where('parent_id', $root->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }

    public function selectTab(int $parentCategoryId): void
    {
        $this->parent_category_id      = $parentCategoryId;
        $this->transaction_category_id = $parentCategoryId; // default = the tab's category
        $this->reference_id            = null;
        $this->project_work_phase      = null;
        $this->resetValidation();
    }

    /** Slug of the active tab (to detect project / supplier tabs). */
    private function activeTabSlug(): ?string
    {
        if (! $this->parent_category_id) {
            return null;
        }
        return TransactionCategory::query()->whereKey($this->parent_category_id)->value('slug');
    }

    private function isProjectTab(): bool
    {
        return $this->activeTabSlug() === 'project-expense';
    }

    private function isSupplierTab(): bool
    {
        return in_array($this->activeTabSlug(), ['supplier-expense', 'vendor-expense'], true);
    }

    public function save(): void
    {
        $this->authorizePermission('accounts.expense.create');
        $this->attachments = $this->normalizedAttachmentIds();

        $rules = [
            'parent_category_id'      => ['required', 'integer', 'exists:transaction_categories,id'],
            'transaction_category_id' => ['required', 'integer', 'exists:transaction_categories,id'],
            'bank_account_id'         => ['required', 'integer', 'exists:bank_accounts,id'],
            'title'                   => ['required', 'string', 'max:200'],
            'date'                    => ['required', 'date'],
            'amount'                  => ['required', 'numeric', 'gt:0'],
            'notes'                   => ['nullable', 'string', 'max:1000'],
            'attachments'             => ['nullable', 'array'],
            'attachments.*'           => ['integer', 'exists:files,id'],
        ];

        if ($this->isProjectTab()) {
            $rules['reference_id'] = ['required', 'integer', 'exists:projects,id'];
            $rules['project_work_phase'] = ['nullable', 'string', 'in:' . implode(',', array_map(fn($c) => $c->value, WorkPhase::cases()))];
        } elseif ($this->isSupplierTab()) {
            $rules['reference_id'] = ['required', 'integer', 'exists:suppliers,id'];
        }

        try {
            $this->validate($rules);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('toast', ['type' => 'error', 'message' => collect($e->validator->errors()->all())->first()]);
            throw $e;
        }

        try {
            $externalData = [];
            if ($this->isProjectTab() && $this->project_work_phase) {
                $externalData = ['project_work_phase' => $this->project_work_phase];
            }

            if ($this->attachments !== []) {
                $externalData['attachments'] = $this->attachments;
            }

            $bpr = BankingPaymentRequest::create([
                'request_no'              => BankingPaymentRequest::generateRequestNo(),
                'source_type'             => TransactionType::EXPENSE->value,
                'transaction_category_id' => $this->transaction_category_id,
                'bank_account_id'         => $this->bank_account_id,
                'amount'                  => round((float) $this->amount, 3),
                'description'             => $this->title,
                'status'                  => 'pending',
                'notes'                   => $this->notes ?: null,
                'requested_by'            => Auth::id(),
                'external_data'           => $externalData ?: null,
            ]);

            // Project / supplier reference via the existing sourceable morph.
            if ($this->isProjectTab() && $this->reference_id) {
                $bpr->sourceable()->associate(Project::find($this->reference_id))->save();
            } elseif ($this->isSupplierTab() && $this->reference_id) {
                $bpr->sourceable()->associate(Supplier::find($this->reference_id))->save();
            }
        } catch (\Throwable $e) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Save failed: ' . $e->getMessage()]);
            return;
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Expense request created and sent to banking.']);
        $this->redirect(route('admin.accounts.expenses.index'), navigate: true);
    }

    private function normalizedAttachmentIds(): array
    {
        return collect($this->attachments)
            ->map(static fn (mixed $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    public function render(): View
    {
        $tabs = $this->parentCategories();

        // Manual category dropdown — children of the active tab + the tab itself.
        $expenseCategories = collect();
        if ($this->parent_category_id) {
            $parent = TransactionCategory::query()->find($this->parent_category_id);
            $children = TransactionCategory::query()
                ->where('parent_id', $this->parent_category_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']);
            $expenseCategories = $parent
                ? collect([(object) ['id' => $parent->id, 'name' => $parent->name . ' (general)']])->concat($children)
                : $children;
        }

        $bankAccounts = BankAccount::query()
            ->where('status', 'active')
            ->orderBy('bank_name')
            ->get(['id', 'bank_name', 'ac_number', 'type']);

        $projects  = $this->isProjectTab()
            ? Project::query()->orderBy('name')->get(['id', 'name'])
            : collect();

        $suppliers = $this->isSupplierTab()
            ? Supplier::query()->orderBy('name')->get(['id', 'name'])
            : collect();

        $workPhases = array_map(fn($case) => ['value' => $case->value, 'label' => $case->label()], WorkPhase::cases());

        return view('livewire.admin.accounts.expense.expense-form', [
            'tabs'              => $tabs,
            'expenseCategories' => $expenseCategories,
            'bankAccounts'     => $bankAccounts,
            'projects'         => $projects,
            'suppliers'        => $suppliers,
            'workPhases'       => $workPhases,
            'isProjectTab'     => $this->isProjectTab(),
            'isSupplierTab'    => $this->isSupplierTab(),
        ])->layout('layouts.admin.admin');
    }
}
