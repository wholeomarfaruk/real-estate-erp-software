<?php

namespace Tests\Feature\Accounts;

use App\Enums\Accounts\AccountType;
use App\Enums\Accounts\EntryMethod;
use App\Livewire\Admin\Accounts\Account\AccountList;
use App\Models\Account;
use App\Models\User;
use App\Services\Accounts\AccountingEntryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AccountReferenceLinkingTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_list_saves_allowed_reference_links(): void
    {
        $user = $this->createUserWithPermissions([
            'accounts.chart.list',
            'accounts.chart.create',
        ]);

        $parent = Account::query()->create([
            'name' => 'Assets',
            'type' => AccountType::ASSET->value,
            'is_active' => true,
        ]);

        $this->actingAs($user);

        Livewire::test(AccountList::class)
            ->set('name', 'Supplier Ledger')
            ->set('type', AccountType::ASSET->value)
            ->set('parent_id', $parent->id)
            ->set('is_active', true)
            ->set('allowed_reference_keys', ['supplier', 'purchase_payable'])
            ->call('save')
            ->assertHasNoErrors();

        $account = Account::query()
            ->with('referenceKeys')
            ->where('name', 'Supplier Ledger')
            ->firstOrFail();

        $this->assertSame(
            ['purchase_payable', 'supplier'],
            $account->referenceKeys->pluck('reference_key')->sort()->values()->all()
        );
        $this->assertSame(
            ['purchase_payable', 'supplier'],
            $account->allowedReferences()->keys()->sort()->values()->all()
        );
    }

    public function test_accounting_entry_service_rejects_payment_reference_not_linked_to_purpose_account(): void
    {
        $user = User::factory()->create();

        $paymentAccount = Account::query()->create([
            'name' => 'Cash',
            'type' => AccountType::ASSET->value,
            'is_active' => true,
        ]);

        $purposeAccount = Account::query()->create([
            'name' => 'Vendor Payable',
            'type' => AccountType::LIABILITY->value,
            'is_active' => true,
        ]);

        $purposeAccount->referenceKeys()->create([
            'reference_key' => 'supplier',
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Selected reference type is not allowed for the chosen account.');

        app(AccountingEntryService::class)->savePayment([
            'date' => '2026-04-26',
            'method' => EntryMethod::CASH->value,
            'payment_account_id' => $paymentAccount->id,
            'purpose_account_id' => $purposeAccount->id,
            'amount' => 500,
            'payee_name' => 'Test Supplier',
            'reference_type' => 'project',
            'reference_id' => 12,
            'notes' => 'Invalid reference check',
        ], null, $user->id);
    }

    public function test_accounting_entry_service_accepts_payment_reference_linked_to_purpose_account(): void
    {
        $user = User::factory()->create();

        $paymentAccount = Account::query()->create([
            'name' => 'Bank',
            'type' => AccountType::ASSET->value,
            'is_active' => true,
        ]);

        $purposeAccount = Account::query()->create([
            'name' => 'Supplier Payable',
            'type' => AccountType::LIABILITY->value,
            'is_active' => true,
        ]);

        $purposeAccount->referenceKeys()->create([
            'reference_key' => 'supplier',
        ]);

        $payment = app(AccountingEntryService::class)->savePayment([
            'date' => '2026-04-26',
            'method' => EntryMethod::BANK->value,
            'payment_account_id' => $paymentAccount->id,
            'purpose_account_id' => $purposeAccount->id,
            'amount' => 800,
            'payee_name' => 'Approved Supplier',
            'reference_type' => 'supplier',
            'reference_id' => 7,
            'notes' => 'Valid reference check',
        ], null, $user->id);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'purpose_account_id' => $purposeAccount->id,
            'reference_type' => 'supplier',
            'reference_id' => 7,
        ]);
        $this->assertSame(2, $payment->transaction->lines()->count());
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function createUserWithPermissions(array $permissions): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::query()->create([
            'name' => 'accounts-reference-linking',
            'guard_name' => 'web',
        ]);

        foreach ($permissions as $permissionName) {
            $permission = Permission::query()->create([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);

            $role->givePermissionTo($permission);
        }

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
