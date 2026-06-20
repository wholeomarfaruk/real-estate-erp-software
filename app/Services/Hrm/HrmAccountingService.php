<?php

namespace App\Services\Hrm;

use App\Enums\Accounts\TransactionType;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Services\Accounts\LedgerService;
use Carbon\Carbon;

class HrmAccountingService
{
    public function __construct(
        protected HrmAccountResolver $accountResolver,
        protected LedgerService $ledger,
    ) {}

    public function createPayrollGenerationTransaction(
        float $amount,
        string $date,
        string $notes,
        int $actorId,
        string $referenceType,
        ?int $referenceId = null
    ): Transaction {
        if ($amount <= 0) {
            throw new \DomainException('Payroll amount must be greater than zero for accounting entry.');
        }

        $salaryExpense = $this->accountResolver->resolveRequiredAccount('salary_expense');
        $salaryPayable = $this->accountResolver->resolveRequiredAccount('salary_payable');
        $categoryId = $this->resolvePayrollCategoryId();

        // DR salary expense (cost) / CR salary payable (liability)
        return $this->ledger->post(
            [
                'datetime' => $this->asDateTime($date),
                'type' => TransactionType::EXPENSE->value,
                'transaction_category_id' => $categoryId,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'created_by' => $actorId,
            ],
            [
                ['account_id' => (int) $salaryExpense->id, 'debit' => $amount, 'credit' => 0,       'notes' => 'Salary expense'],
                ['account_id' => (int) $salaryPayable->id, 'debit' => 0,       'credit' => $amount, 'notes' => 'Salary payable'],
            ],
        );
    }

    public function createPayrollPaymentTransaction(
        float $amount,
        string $date,
        ?string $paymentMethod,
        string $notes,
        int $actorId,
        string $referenceType,
        ?int $referenceId = null,
        ?int $paymentAccountId = null,
        ?int $transactionCategoryId = null,
        ?string $name = null
    ): Transaction {
        if ($amount <= 0) {
            throw new \DomainException('Payroll payment amount must be greater than zero for accounting entry.');
        }

        $normalizedMethod = $paymentMethod ?: 'cash';
        $salaryPayable = $this->accountResolver->resolveRequiredAccount('salary_payable');
        $paymentAccount = $paymentAccountId
            ? (object) ['id' => $paymentAccountId]
            : $this->accountResolver->resolvePaymentAccountByMethod($paymentMethod);
        $categoryId = $transactionCategoryId ?: $this->resolvePayrollCategoryId();

        // DR salary payable (settle liability) / CR cash-bank (money leaves)
        return $this->ledger->post(
            [
                'datetime' => $this->asDateTime($date),
                'type' => TransactionType::EXPENSE->value,
                'transaction_category_id' => $categoryId,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'method' => $normalizedMethod,
                'name' => $name,
                'notes' => $notes,
                'created_by' => $actorId,
            ],
            [
                ['account_id' => (int) $salaryPayable->id, 'debit' => $amount, 'credit' => 0,       'notes' => 'Salary payable'],
                ['account_id' => (int) $paymentAccount->id, 'debit' => 0,      'credit' => $amount, 'notes' => 'Cash/Bank'],
            ],
        );
    }

    public function createEmployeeAdvanceTransaction(
        float $amount,
        string $date,
        ?string $paymentMethod,
        string $notes,
        int $actorId,
        string $referenceType,
        ?int $referenceId = null
    ): Transaction {
        if ($amount <= 0) {
            throw new \DomainException('Advance amount must be greater than zero for accounting entry.');
        }

        $normalizedMethod = $paymentMethod ?: 'cash';
        $employeeAdvance = $this->accountResolver->resolveRequiredAccount('employee_advance');
        $paymentAccount = $this->accountResolver->resolvePaymentAccountByMethod($paymentMethod);
        $categoryId = $this->resolveCategoryIdBySlug('employee-advance');

        // DR employee advance (receivable) / CR cash-bank (money leaves)
        return $this->ledger->post(
            [
                'datetime' => $this->asDateTime($date),
                'type' => TransactionType::ADVANCE->value,
                'transaction_category_id' => $categoryId,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'method' => $normalizedMethod,
                'notes' => $notes,
                'created_by' => $actorId,
            ],
            [
                ['account_id' => (int) $employeeAdvance->id, 'debit' => $amount, 'credit' => 0,       'notes' => 'Employee advance'],
                ['account_id' => (int) $paymentAccount->id,  'debit' => 0,       'credit' => $amount, 'notes' => 'Cash/Bank'],
            ],
        );
    }

    protected function asDateTime(string $date): string
    {
        return Carbon::parse($date)->startOfDay()->format('Y-m-d H:i:s');
    }

    protected function resolvePayrollCategoryId(): ?int
    {
        return TransactionCategory::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->where('slug', 'payroll')
                    ->orWhere(function ($subQuery): void {
                        $subQuery->where('type', 'expense')
                            ->whereRaw('LOWER(name) = ?', ['payroll']);
                    });
            })
            ->value('id');
    }

    protected function resolveCategoryIdBySlug(string $slug): ?int
    {
        return TransactionCategory::query()
            ->where('is_active', true)
            ->where('slug', $slug)
            ->value('id');
    }
}
