<?php

namespace Tests\Feature\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\Account;
use App\Models\AccountCollection;
use App\Models\Expense;
use App\Models\Panel;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StatementReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_view_statement_report(): void
    {
        $user = $this->createAdminUserWithPermissions([
            'accounts.reports.statement.view',
        ]);

        $accounts = $this->seedStatementData($user);

        $response = $this->actingAs($user)->get(route('admin.accounts.reports.statement', [
            'from_date' => '2026-04-25',
            'to_date' => '2026-04-25',
            'bank_account_id' => $accounts['bank']->id,
        ]));

        $response->assertOk();
        $response->assertSee('Statement Sheet');
        $response->assertSee('6,300.00');
        $response->assertSee('200.00');
        $response->assertSee('300.00');
        $response->assertSee('6,800.00');

        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $response->assertDontSee('Download PDF');
        }
    }

    public function test_authorized_user_can_export_statement_report_pdf(): void
    {
        $user = $this->createAdminUserWithPermissions([
            'accounts.reports.statement.export',
        ]);

        $accounts = $this->seedStatementData($user);

        $response = $this->actingAs($user)->get(route('admin.accounts.reports.statement.export', [
            'from_date' => '2026-04-25',
            'to_date' => '2026-04-25',
            'bank_account_id' => $accounts['bank']->id,
        ]));

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $response->assertOk();
            $response->assertHeader('content-type', 'application/pdf');

            return;
        }

        $response->assertNotFound();
    }

    public function test_authorized_user_can_open_printable_statement_report(): void
    {
        $user = $this->createAdminUserWithPermissions([
            'accounts.reports.statement.print',
        ]);

        $accounts = $this->seedStatementData($user);

        $response = $this->actingAs($user)->get(route('admin.accounts.reports.statement.print', [
            'from_date' => '2026-04-25',
            'to_date' => '2026-04-25',
            'bank_account_id' => $accounts['bank']->id,
        ]));

        $response->assertOk();
        $response->assertSee('Daily Statement');
        $response->assertSee('Closing Balance: Cash HO');
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function createAdminUserWithPermissions(array $permissions): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::query()->create(['name' => 'accounts', 'guard_name' => 'web']);

        foreach ($permissions as $permissionName) {
            $permission = Permission::query()->create(['name' => $permissionName, 'guard_name' => 'web']);
            $role->givePermissionTo($permission);
        }

        $user = User::factory()->create();
        $user->assignRole($role);

        $panel = new Panel();
        $panel->name = 'Administration';
        $panel->slug = 'admin';
        $panel->save();

        $user->panels()->attach($panel->id);

        return $user;
    }

    /**
     * @return array<string, \App\Models\Account>
     */
    protected function seedStatementData(User $user): array
    {
        $assets = Account::query()->create([
            'name' => 'Assets',
            'type' => 'asset',
            'is_active' => true,
        ]);

        $incomeRoot = Account::query()->create([
            'name' => 'Income',
            'type' => 'income',
            'is_active' => true,
        ]);

        $expenseRoot = Account::query()->create([
            'name' => 'Expenses',
            'type' => 'expense',
            'is_active' => true,
        ]);

        $cash = Account::query()->create([
            'parent_id' => $assets->id,
            'name' => 'Cash',
            'type' => 'asset',
            'is_active' => true,
        ]);

        $bank = Account::query()->create([
            'parent_id' => $assets->id,
            'name' => 'Bank',
            'type' => 'asset',
            'is_active' => true,
        ]);

        $employeeAdvance = Account::query()->create([
            'parent_id' => $assets->id,
            'name' => 'Employee Advance',
            'type' => 'asset',
            'is_active' => true,
        ]);

        $otherIncome = Account::query()->create([
            'parent_id' => $incomeRoot->id,
            'name' => 'Other Income',
            'type' => 'income',
            'is_active' => true,
        ]);

        $officeExpense = Account::query()->create([
            'parent_id' => $expenseRoot->id,
            'name' => 'Office Expense',
            'type' => 'expense',
            'is_active' => true,
        ]);

        $this->createCollection(
            user: $user,
            date: '2026-04-24',
            amount: 2000,
            collectionAccount: $bank,
            targetAccount: $otherIncome,
            collectionNo: 'COL-OPEN-BANK'
        );

        $this->createCollection(
            user: $user,
            date: '2026-04-24',
            amount: 800,
            collectionAccount: $cash,
            targetAccount: $otherIncome,
            collectionNo: 'COL-OPEN-CASH'
        );

        $this->createCollection(
            user: $user,
            date: '2026-04-25',
            amount: 5000,
            collectionAccount: $bank,
            targetAccount: $otherIncome,
            collectionNo: 'COL-000001'
        );

        $this->createJournal(
            user: $user,
            date: '2026-04-25',
            referenceType: 'bank_transfer',
            lines: [
                ['account_id' => $cash->id, 'debit' => 700, 'credit' => 0, 'description' => 'Cash received from bank'],
                ['account_id' => $bank->id, 'debit' => 0, 'credit' => 700, 'description' => 'Bank transfer out'],
            ]
        );

        $this->createExpense(
            user: $user,
            date: '2026-04-25',
            amount: 1000,
            expenseAccount: $officeExpense,
            paymentAccount: $cash,
            expenseNo: 'EXP-000001',
            title: 'Office Expense Voucher'
        );

        $this->createJournal(
            user: $user,
            date: '2026-04-25',
            referenceType: 'hrm_employee_advance',
            lines: [
                ['account_id' => $employeeAdvance->id, 'debit' => 300, 'credit' => 0, 'description' => 'Employee advance issued'],
                ['account_id' => $cash->id, 'debit' => 0, 'credit' => 300, 'description' => 'Cash paid for employee advance'],
            ]
        );

        return [
            'cash' => $cash,
            'bank' => $bank,
            'employee_advance' => $employeeAdvance,
        ];
    }

    protected function createCollection(
        User $user,
        string $date,
        float $amount,
        Account $collectionAccount,
        Account $targetAccount,
        string $collectionNo
    ): void {
        $transaction = Transaction::query()->create([
            'date' => $date,
            'type' => TransactionType::COLLECTION->value,
            'reference_type' => 'collection',
            'reference_id' => null,
            'created_by' => $user->id,
        ]);

        $collection = AccountCollection::query()->create([
            'transaction_id' => $transaction->id,
            'collection_no' => $collectionNo,
            'date' => $date,
            'method' => 'cash',
            'collection_account_id' => $collectionAccount->id,
            'target_account_id' => $targetAccount->id,
            'amount' => $amount,
            'payer_name' => 'Test Payer',
            'collection_type' => 'other',
            'created_by' => $user->id,
        ]);

        $transaction->update([
            'reference_id' => $collection->id,
        ]);

        $transaction->lines()->createMany([
            [
                'account_id' => $collectionAccount->id,
                'debit' => $amount,
                'credit' => 0,
                'description' => 'Collection debit entry',
            ],
            [
                'account_id' => $targetAccount->id,
                'debit' => 0,
                'credit' => $amount,
                'description' => 'Collection credit entry',
            ],
        ]);
    }

    protected function createExpense(
        User $user,
        string $date,
        float $amount,
        Account $expenseAccount,
        Account $paymentAccount,
        string $expenseNo,
        string $title
    ): void {
        $transaction = Transaction::query()->create([
            'date' => $date,
            'type' => TransactionType::EXPENSE->value,
            'reference_type' => 'expense',
            'reference_id' => null,
            'created_by' => $user->id,
        ]);

        $expense = Expense::query()->create([
            'transaction_id' => $transaction->id,
            'expense_no' => $expenseNo,
            'date' => $date,
            'title' => $title,
            'expense_account_id' => $expenseAccount->id,
            'payment_account_id' => $paymentAccount->id,
            'amount' => $amount,
            'created_by' => $user->id,
        ]);

        $transaction->update([
            'reference_id' => $expense->id,
        ]);

        $transaction->lines()->createMany([
            [
                'account_id' => $expenseAccount->id,
                'debit' => $amount,
                'credit' => 0,
                'description' => 'Expense debit entry',
            ],
            [
                'account_id' => $paymentAccount->id,
                'debit' => 0,
                'credit' => $amount,
                'description' => 'Expense credit entry',
            ],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    protected function createJournal(User $user, string $date, string $referenceType, array $lines): void
    {
        $transaction = Transaction::query()->create([
            'date' => $date,
            'type' => TransactionType::JOURNAL->value,
            'reference_type' => $referenceType,
            'reference_id' => 1,
            'created_by' => $user->id,
        ]);

        $transaction->lines()->createMany($lines);
    }
}
