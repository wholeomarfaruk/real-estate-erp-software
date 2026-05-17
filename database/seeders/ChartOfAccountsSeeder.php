<?php

namespace Database\Seeders;

use App\Enums\Accounts\AccountSubType;
use App\Enums\Accounts\AccountType;
use App\Models\Account;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $structure = [
            [
                'name' => 'Office Cash',
                'type' => AccountType::ASSET->value,
                'sub_type' => AccountSubType::CASH->value,
            ],
            [
                'name' => 'DBBL Bank',
                'type' => AccountType::ASSET->value,
                'sub_type' => AccountSubType::BANK->value,
            ],

        ];

        foreach ($structure as $group) {
            $parent = Account::query()->firstOrCreate(
                [
                    'name' => $group['name'],
                    'type' => $group['type'],
                    'sub_type' => $group['sub_type'],
                    'parent_id' => null,
                ],
                [
                    'is_active' => true,
                ]
            );

            if (! $parent->is_active) {
                $parent->is_active = true;
                $parent->save();
            }

        }
    }
}
