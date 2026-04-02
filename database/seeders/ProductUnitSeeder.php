<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = ['pcs', 'kg', 'litre', 'm', 'cm', 'mm', 'g', 'fit', 'sqft', 'sqm'];
        foreach ($units as $unit) {
            \App\Models\ProductUnit::create([
                'name' => $unit
            ]);
        }
    }
}
