<?php

namespace Tests\Feature\Reports;

use App\Enums\Accounts\AccountType;
use App\Enums\Accounts\TransactionType;
use App\Models\Account;
use App\Models\Expense;
use App\Models\Panel;
use App\Models\Project;
use App\Models\Transaction;
use App\Models\TransactionLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AccountReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_report_and_exports_render_expected_data(): void
    {
        $user = $this->createUserWithPermissions(['accounts.report.view']);
        [$cashAccount, $expenseAccount] = $this->createBaseAccounts();
        $project = $this->createProject('Tower One', 'PRJ-001');

        $this->createExpenseEntry(
            cashAccount: $cashAccount,
            expenseAccount: $expenseAccount,
            user: $user,
            amount: 800,
            title: 'Site Fuel',
            date: '2026-04-20',
            referenceType: 'project',
            referenceId: $project->id,
        );

        $query = [
            'from_date' => '2026-04-01',
            'to_date' => '2026-04-30',
            'project_id' => $project->id,
        ];

        $response = $this->actingAs($user)->get(route('admin.accounts.reports.expenses', $query));

        $response->assertOk();
        $response->assertSee('Expense Report');
        $response->assertSee('Site Fuel');
        $response->assertSee('800.00');

        $excelResponse = $this->actingAs($user)->get(route('admin.accounts.reports.export.excel', array_merge([
            'report' => 'expense',
        ], $query)));

        $excelResponse->assertOk();
        $this->assertStringContainsString('.xls', (string) $excelResponse->headers->get('content-disposition'));

        $pdfResponse = $this->actingAs($user)->get(route('admin.accounts.reports.export.pdf', array_merge([
            'report' => 'expense',
        ], $query)));

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdfResponse->assertOk();
            $this->assertStringContainsString('application/pdf', (string) $pdfResponse->headers->get('content-type'));

            return;
        }

        $pdfResponse->assertNotFound();
    }

    public function test_trial_balance_and_account_ledger_reports_render_transaction_balances(): void
    {
        $user = $this->createUserWithPermissions(['accounts.report.view']);
        [$cashAccount, $expenseAccount] = $this->createBaseAccounts();

        $this->createExpenseEntry(
            cashAccount: $cashAccount,
            expenseAccount: $expenseAccount,
            user: $user,
            amount: 650,
            title: 'Office Supplies',
            date: '2026-04-18',
        );

        $trialBalanceResponse = $this->actingAs($user)->get(route('admin.accounts.reports.trial-balance', [
            'from_date' => '2026-04-01',
            'to_date' => '2026-04-30',
        ]));

        $trialBalanceResponse->assertOk();
        $trialBalanceResponse->assertSee('Trial Balance');
        $trialBalanceResponse->assertSee('Cash');
        $trialBalanceResponse->assertSee('Office Expense');
        $trialBalanceResponse->assertSee('650.00');

        $ledgerResponse = $this->actingAs($user)->get(route('admin.accounts.reports.account-ledger', [
            'from_date' => '2026-04-01',
            'to_date' => '2026-04-30',
            'account_id' => $expenseAccount->id,
        ]));

        $ledgerResponse->assertOk();
        $ledgerResponse->assertSee('Account Ledger');
        $ledgerResponse->assertSee('Office Expense');
        $ledgerResponse->assertSee('Office Supplies');
        $ledgerResponse->assertSee('650.00');
    }

    public function test_project_wise_expense_report_groups_entries_by_project_reference(): void
    {
        $user = $this->createUserWithPermissions(['accounts.report.view']);
        [$cashAccount, $expenseAccount] = $this->createBaseAccounts();
        $projectA = $this->createProject('Lake View', 'PRJ-100');
        $projectB = $this->createProject('Hill View', 'PRJ-200');

        $this->createExpenseEntry(
            cashAccount: $cashAccount,
            expenseAccount: $expenseAccount,
            user: $user,
            amount: 500,
            title: 'Labor',
            date: '2026-04-10',
            referenceType: 'project',
            referenceId: $projectA->id,
        );

        $this->createExpenseEntry(
            cashAccount: $cashAccount,
            expenseAccount: $expenseAccount,
            user: $user,
            amount: 250,
            title: 'Transport',
            date: '2026-04-12',
            referenceType: 'project',
            referenceId: $projectA->id,
        );

        $this->createExpenseEntry(
            cashAccount: $cashAccount,
            expenseAccount: $expenseAccount,
            user: $user,
            amount: 300,
            title: 'Materials',
            date: '2026-04-15',
            referenceType: 'project',
            referenceId: $projectB->id,
        );

        $response = $this->actingAs($user)->get(route('admin.accounts.reports.project-wise-expense', [
            'from_date' => '2026-04-01',
            'to_date' => '2026-04-30',
        ]));

        $response->assertOk();
        $response->assertSee('Project Wise Expense');
        $response->assertSee('Lake View');
        $response->assertSee('Hill View');
        $response->assertSee('750.00');
        $response->assertSee('300.00');
    }

    /**
     * @return array{0:\App\Models\Account,1:\App\Models\Account}
     */
    protected function createBaseAccounts(): array
    {
        $cashAccount = Account::query()->create([
            'name' => 'Cash',
            'type' => AccountType::ASSET->value,
            'is_active' => true,
        ]);

        $expenseAccount = Account::query()->create([
            'name' => 'Office Expense',
            'type' => AccountType::EXPENSE->value,
            'is_active' => true,
        ]);

        return [$cashAccount, $expenseAccount];
    }

    protected function createProject(string $name, string $code): Project
    {
        return Project::query()->create([
            'name' => $name,
            'code' => $code,
            'status' => 'ongoing',
        ]);
    }

    protected function createExpenseEntry(
        Account $cashAccount,
        Account $expenseAccount,
        User $user,
        float $amount,
        string $title,
        string $date,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): Expense {
        $transaction = Transaction::query()->create([
            'date' => $date,
            'type' => TransactionType::EXPENSE->value,
            'reference_type' => 'expense',
            'reference_id' => null,
            'notes' => $title,
            'created_by' => $user->id,
        ]);

        $expense = Expense::query()->create([
            'transaction_id' => $transaction->id,
            'expense_no' => 'EXP-'.str_pad((string) ($transaction->id + 100), 4, '0', STR_PAD_LEFT),
            'date' => $date,
            'title' => $title,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'expense_account_id' => $expenseAccount->id,
            'payment_account_id' => $cashAccount->id,
            'amount' => $amount,
            'notes' => $title,
            'created_by' => $user->id,
        ]);

        $transaction->update([
            'reference_id' => $expense->id,
        ]);

        TransactionLine::query()->create([
            'transaction_id' => $transaction->id,
            'account_id' => $expenseAccount->id,
            'debit' => $amount,
            'credit' => 0,
            'description' => $title,
        ]);

        TransactionLine::query()->create([
            'transaction_id' => $transaction->id,
            'account_id' => $cashAccount->id,
            'debit' => 0,
            'credit' => $amount,
            'description' => $title,
        ]);

        return $expense;
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function createUserWithPermissions(array $permissions): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::query()->create([
            'name' => 'account-reports-'.uniqid(),
            'guard_name' => 'web',
        ]);

        foreach ($permissions as $permissionName) {
            $permission = Permission::query()->create([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);

            $role->givePermissionTo($permission);
        }

        $user = User::query()->create([
            'name' => 'Report User',
            'email' => 'report-'.uniqid().'@example.com',
            'password' => Hash::make('password'),
        ]);

        $user->assignRole($role);
        $user->givePermissionTo($permissions);

        $panel = new Panel;
        $panel->name = 'Administration';
        $panel->slug = 'admin';
        $panel->save();

        $user->panels()->attach($panel->id);

        return $user;
    }
}
