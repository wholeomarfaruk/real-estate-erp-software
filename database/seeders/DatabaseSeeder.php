<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            PanelSeeder::class,
            ProductUnitSeeder::class,
            UnitTypeSeeder::class,
            ChartOfAccountsSeeder::class,
            AccountingEventSeeder::class,
            TransactionCategorySeeder::class,
            NumberSequenceSeeder::class,
            ExpenseTypesSeeder::class,
            FeatureSeeder::class,
            FeatureAccountMappingSeeder::class,
            ExpenseCategorySeeder::class,
            UserSeeder::class,
            MarketingSeeder::class,
            SmsGatewaySeeder::class,
            AssignPermissionSeeder::class,
        ]);
    }
}
