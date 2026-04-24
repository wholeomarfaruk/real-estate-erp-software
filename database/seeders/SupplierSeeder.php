<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'id' => 1,
                'name' => 'ABC Construction Materials',
                'contact_person' => 'John Doe',
                'phone' => '1234567890',
                'email' => 'sZ4m9@example.com',
                'status' => 1,
                'address' => '123 Main Street, Anytown, USA',
            ],
            [
                'id' => 2,
                'name' => 'XYZ Building Supplies',
                'contact_person' => 'Jane Smith',
                'phone' => '9876543210',
                'email' => 'BwB0q@example.com',
                'status' => 1,
                'address' => '456 Elm Street, Anytown, USA',
            ]
        ];

        foreach ($suppliers as $supplier) {
            \App\Models\Supplier::updateOrCreate(['id' => $supplier['id']], $supplier);
        }

    }
}
