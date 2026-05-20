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
        // Parent accounts
   


        // Children
        Account::query()->firstOrCreate(
            ['name' => 'Office Cash', 'type' => AccountType::BANK->value, 'parent_id' => null],
            ['sub_type' => AccountSubType::CASH->value, 'is_active' => true]
        );

        Account::query()->firstOrCreate(
            ['name' => 'DBBL Bank', 'type' => AccountType::BANK->value, 'parent_id' => null],
            ['sub_type' => AccountSubType::BANK->value, 'is_active' => true]
        );
    }
}
