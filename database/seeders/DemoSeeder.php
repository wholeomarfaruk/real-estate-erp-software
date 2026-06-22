<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds for demo data.
     * This seeder is called from DatabaseSeeder and loads all demo data.
     * Run with: php artisan db:seed
     */
    public function run(): void
    {
        $this->call([
            EmployeeSeeder::class,
            SupplierSeeder::class,
            ProductSeeder::class,
            CustomerSeeder::class,
            PropertySeeder::class,
            StoreSeeder::class,
            LeadSeeder::class,
            DemoTestDataSeeder::class,
        ]);
    }
}
