<?php

namespace Tests\Feature\Accounts;

use App\Models\Account;
use App\Models\BankAccount;
use App\Models\Panel;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DailyStatementReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_open_daily_statement_page_and_preview(): void
    {
        $user = $this->createAdminUserWithPermissions([
            'accounts.reports.statement.view',
        ]);

        $accounts = $this->seedDailyStatementData($user);

        $pageResponse = $this->actingAs($user)->get(route('admin.accounts.reports.daily-statement', [
            'report_date' => '2026-04-25',
            'bank_account_id' => $accounts['bank']->id,
        ]));

        $pageResponse->assertOk();
        $pageResponse->assertSee('Daily Statement');
        $pageResponse->assertSee('Live Preview');

        $previewResponse = $this->actingAs($user)->get(route('admin.accounts.reports.daily-statement.preview', [
            'report_date' => '2026-04-25',
            'bank_account_id' => $accounts['bank']->id,
        ]));

        $previewResponse->assertOk();
        $previewResponse->assertSee('Daily Statement Sheet');
        $previewResponse->assertSee('DS-20260425-B'.$accounts['bank']->id);
        $previewResponse->assertSee('Main Bank');
        $previewResponse->assertSee('3,000.00');
        $previewResponse->assertSee('400.00');
    }

    public function test_banking_reports_page_contains_daily_statement_link(): void
    {
        $user = $this->createAdminUserWithPermissions([
            'accounts.reports.statement.view',
        ]);

        $response = $this->actingAs($user)->get(route('admin.accounts.banking.reports'));

        $response->assertOk();
        $response->assertSee('Daily Statement');
        $response->assertSee(route('admin.accounts.reports.daily-statement'), false);
    }

    public function test_authorized_user_can_download_daily_statement_pdf(): void
    {
        $user = $this->createAdminUserWithPermissions([
            'accounts.reports.statement.export',
        ]);

        $accounts = $this->seedDailyStatementData($user);

        $response = $this->actingAs($user)->get(route('admin.accounts.reports.daily-statement.pdf', [
            'report_date' => '2026-04-25',
            'bank_account_id' => $accounts['bank']->id,
        ]));

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $response->assertOk();
            $response->assertHeader('content-type', 'application/pdf');

            return;
        }

        $response->assertNotFound();
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

        $panel = Panel::query()->create([
            'name' => 'Administration',
            'slug' => 'admin',
        ]);

        $user->panels()->attach($panel->id);

        return $user;
    }

    /**
     * @return array<string, \App\Models\Account>
     */
    protected function seedDailyStatementData(User $user): array
    {
        $assets = Account::query()->create([
            'name' => 'Assets',
            'type' => 'asset',
            'is_active' => true,
        ]);

        $bank = Account::query()->create([
            'parent_id' => $assets->id,
            'name' => 'Main Bank',
            'type' => 'asset',
            'sub_type' => 'bank',
            'is_active' => true,
        ]);

        $cash = Account::query()->create([
            'parent_id' => $assets->id,
            'name' => 'Cash HO',
            'type' => 'asset',
            'sub_type' => 'cash',
            'is_active' => true,
        ]);

        $advance = Account::query()->create([
            'parent_id' => $assets->id,
            'name' => 'Advance',
            'type' => 'asset',
            'is_active' => true,
        ]);

        BankAccount::query()->create([
            'type' => 'bank',
            'bank_name' => 'Main Bank',
            'account_id' => $bank->id,
        ]);

        $incomeCategory = TransactionCategory::query()->create([
            'name' => 'Other Income',
            'type' => 'income',
            'slug' => 'other-income',
            'is_active' => true,
        ]);

        $expenseCategory = TransactionCategory::query()->create([
            'name' => 'Office Expense',
            'type' => 'expense',
            'slug' => 'office-expense',
            'is_active' => true,
        ]);

        $advanceCategory = TransactionCategory::query()->create([
            'name' => 'Employee Advance',
            'type' => 'advance',
            'slug' => 'employee-advance',
            'is_active' => true,
        ]);

        Transaction::query()->create([
            'account_id' => $bank->id,
            'datetime' => '2026-04-24 09:00:00',
            'type' => 'income',
            'transaction_category_id' => $incomeCategory->id,
            'debit' => 2000,
            'credit' => 0,
            'notes' => 'Opening bank balance',
            'created_by' => $user->id,
        ]);

        Transaction::query()->create([
            'account_id' => $cash->id,
            'datetime' => '2026-04-24 09:30:00',
            'type' => 'income',
            'transaction_category_id' => $incomeCategory->id,
            'debit' => 500,
            'credit' => 0,
            'notes' => 'Opening cash balance',
            'created_by' => $user->id,
        ]);

        Transaction::query()->create([
            'account_id' => $bank->id,
            'datetime' => '2026-04-25 10:00:00',
            'type' => 'income',
            'transaction_category_id' => $incomeCategory->id,
            'debit' => 3000,
            'credit' => 0,
            'name' => 'Client collection',
            'notes' => 'Apartment booking received',
            'created_by' => $user->id,
        ]);

        Transaction::query()->create([
            'account_id' => $cash->id,
            'datetime' => '2026-04-25 11:00:00',
            'type' => 'income',
            'transaction_category_id' => $incomeCategory->id,
            'debit' => 150,
            'credit' => 0,
            'name' => 'Petty income',
            'notes' => 'Miscellaneous collection',
            'created_by' => $user->id,
        ]);

        Transaction::query()->create([
            'account_id' => $cash->id,
            'datetime' => '2026-04-25 12:00:00',
            'type' => 'expense',
            'transaction_category_id' => $expenseCategory->id,
            'debit' => 0,
            'credit' => 200,
            'name' => 'Office snacks',
            'notes' => 'Office snacks',
            'created_by' => $user->id,
        ]);

        Transaction::query()->create([
            'account_id' => $bank->id,
            'datetime' => '2026-04-25 15:00:00',
            'type' => 'advance',
            'transaction_category_id' => $advanceCategory->id,
            'debit' => 0,
            'credit' => 400,
            'name' => 'Engineer advance',
            'notes' => 'Site engineer advance released',
            'created_by' => $user->id,
        ]);

        Transaction::query()->create([
            'account_id' => $advance->id,
            'datetime' => '2026-04-25 15:00:00',
            'type' => 'advance',
            'transaction_category_id' => $advanceCategory->id,
            'debit' => 400,
            'credit' => 0,
            'name' => 'Engineer advance',
            'notes' => 'Site engineer advance released',
            'created_by' => $user->id,
        ]);

        return [
            'bank' => $bank,
            'cash' => $cash,
            'advance' => $advance,
        ];
    }
}
