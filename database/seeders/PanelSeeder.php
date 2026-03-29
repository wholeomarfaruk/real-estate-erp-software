<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PanelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Panel::insert([
            [
                'id' => 1,
                'name' => 'Admin',
                'slug' => 'admin',
            ]
        ]);
    }
}
