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
        $units = ['piece', 'kg', 'litre', 'm', 'cm', 'mm', 'g', 'fit', 'sqft', 'sqm','bag','m3','l','m2','bag'];
        foreach ($units as $unit) {
            \App\Models\ProductUnit::updateOrCreate(
                [
                    'name' => $unit
                ],
                [
                    'name' => $unit
                ]
            );
        }
    }
}
