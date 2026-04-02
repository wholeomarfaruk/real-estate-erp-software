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
            AssignPermissionSeeder::class,
            StoreSeeder::class,
        ]);

        User::factory()->create([
            'name' => 'superadmin',
            'email' => 'superadmin@gmail.com',
            'password' => bcrypt('password'),
        ]);

        $user = User::find(1);
        $user->assignRole('superadmin');
        $user->panels()->attach(1);
    }
}