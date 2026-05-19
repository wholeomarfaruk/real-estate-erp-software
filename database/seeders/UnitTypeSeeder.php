<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Flat',    'slug' => 'flat'],
            ['name' => 'Shop',    'slug' => 'shop'],
            ['name' => 'Parking', 'slug' => 'parking'],
            ['name' => 'Hall',    'slug' => 'hall'],
            ['name' => 'Office',  'slug' => 'office'],
            ['name' => 'Plot',    'slug' => 'plot'],
        ];

        foreach ($types as $type) {
            DB::table('unit_types')->upsert(
                array_merge($type, ['created_at' => now(), 'updated_at' => now()]),
                ['slug'],
                ['name', 'updated_at']
            );
        }
    }
}
