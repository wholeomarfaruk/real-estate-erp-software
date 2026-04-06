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
        $panels= [
            ['id' => 1, 'name' => 'Admin', 'slug' => 'admin'],
            ['id' => 2, 'name' => 'Engineers', 'slug' => 'engineers'],
            ['id' => 3, 'name' => 'suppliers', 'slug' => 'suppliers'],
            ['id' => 6, 'name' => 'Managing Director', 'slug' => 'managingdirector'],
            ['id' => 7, 'name' => 'Chairman', 'slug' => 'chairman'],
        ];
        foreach ($panels as $panel) {
            \App\Models\Panel::updateOrCreate(
                [
                    'id' => $panel['id'],
                ],
                [
                    'name' => $panel['name'],
                    'slug' => $panel['slug'],
                ]
            );
        }
    }
}

