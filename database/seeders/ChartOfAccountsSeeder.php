<?php

namespace Database\Seeders;

use App\Enums\Accounts\AccountGroupType;
use App\Enums\Accounts\AccountSubType;
use App\Enums\Accounts\AccountType;
use App\Models\Account;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        // 5 top-level group parents (locked — never deletable), each with its
        // postable child ledger accounts. `type` stays the small enum
        // (cash/bank/mfs/wallet/ledger); the accounting classification lives in
        // `group`. Keyed on the unique `code` so re-seeding is idempotent.
        $tree = [
            'ASSET' => [
                'name'     => 'Assets',
                'group'    => AccountGroupType::ASSET,
                'children' => [
                    ['code' => 'ASSET-CASH',    'name' => 'Cash',                'type' => AccountType::CASH, 'sub_type' => AccountSubType::CASH],
                    ['code' => 'ASSET-BANK',    'name' => 'Bank',                'type' => AccountType::BANK, 'sub_type' => AccountSubType::BANK],
                    ['code' => 'ASSET-AR',      'name' => 'Accounts Receivable', 'type' => AccountType::LEDGER],
                    ['code' => 'ASSET-SUP-ADV', 'name' => 'Supplier Advance',    'type' => AccountType::LEDGER],
                    ['code' => 'ASSET-INV',     'name' => 'Inventory',           'type' => AccountType::LEDGER],
                ],
            ],
            'LIAB' => [
                'name'     => 'Liabilities',
                'group'    => AccountGroupType::LIABILITY,
                'children' => [
                    ['code' => 'LIAB-ADV',     'name' => 'Customer Advance',   'type' => AccountType::LEDGER],
                    ['code' => 'LIAB-DEPOSIT', 'name' => 'Security Deposit',   'type' => AccountType::LEDGER],
                    ['code' => 'LIAB-AP',      'name' => 'Accounts Payable',   'type' => AccountType::LEDGER],
                ],
            ],
            'INC' => [
                'name'     => 'Income',
                'group'    => AccountGroupType::INCOME,
                'children' => [
                    ['code' => 'INC-SALES', 'name' => 'Property Sales', 'type' => AccountType::LEDGER],
                    ['code' => 'INC-RENT',  'name' => 'Rent Revenue',   'type' => AccountType::LEDGER],
                ],
            ],
            'EXP' => [
                'name'     => 'Expenses',
                'group'    => AccountGroupType::EXPENSE,
                'children' => [
                    ['code' => 'EXP-OFFICE', 'name' => 'Office Expense', 'type' => AccountType::LEDGER],
                    ['code' => 'EXP-SALARY', 'name' => 'Salary Expense', 'type' => AccountType::LEDGER],
                    ['code' => 'EXP-PROJ',   'name' => 'Project Expenses', 'type' => AccountType::LEDGER],
                ],
            ],
            'EQTY' => [
                'name'     => 'Equity',
                'group'    => AccountGroupType::EQUITY,
                'children' => [
                    ['code' => 'EQTY-CAPITAL',  'name' => 'Capital',           'type' => AccountType::LEDGER],
                    ['code' => 'EQTY-RETAINED', 'name' => 'Retained Earnings', 'type' => AccountType::LEDGER],
                ],
            ],
        ];

        foreach ($tree as $code => $group) {
            $parent = Account::query()->firstOrCreate(
                ['code' => $code],
                [
                    'name'      => $group['name'],
                    'type'      => AccountType::LEDGER->value,
                    'group'     => $group['group']->value,
                    'parent_id' => null,
                    'is_active' => true,
                    'is_locked' => true,
                ]
            );

            foreach ($group['children'] as $child) {
                Account::query()->firstOrCreate(
                    ['code' => $child['code']],
                    [
                        'name'      => $child['name'],
                        'type'      => $child['type']->value,
                        'group'     => $group['group']->value,
                        'parent_id' => $parent->id,
                        'sub_type'  => isset($child['sub_type']) ? $child['sub_type']->value : null,
                        'is_active' => true,
                        'is_locked' => false,
                    ]
                );
            }
        }
    }
}
