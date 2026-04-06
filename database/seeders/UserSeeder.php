<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $users = [
                [
                    'id' => 1,
                    'name' => 'superadmin',
                    'email' => 'superadmin@gmail.com',
                    'password' => bcrypt('password'),
                ],
                [
                    'id' => 2,
                    'name' => 'Admin',
                    'email' => 'admin@gmail.com',
                    'password' => bcrypt('password'),
                ],
                [
                    'id' => 3,
                    'name' => 'Store Manager',
                    'email' => 'storemanager@gmail.com',
                    'password' => bcrypt('password'),
                ],
                [
                    'id' => 4,
                    'name' => 'Accountant',
                    'email' => 'accountant@gmail.com',
                    'password' => bcrypt('password'),
                ],
                 [
                    'id' => 5,
                    'name' => 'Project Manager',
                    'email' => 'projectmanager@gmail.com',
                    'password' => bcrypt('password'),
                ],
                 [
                    'id' => 6,
                    'name' => 'Chairman',
                    'email' => 'chairman@gmail.com',
                    'password' => bcrypt('password'),
                ],
                 [
                    'id' => 7,
                    'name' => 'Chief Engineer',
                    'email' => 'chiefengineer@gmail.com',
                    'password' => bcrypt('password'),
                ],
                 [
                    'id' => 8,
                    'name' => 'Site Engineer',
                    'email' => 'siteengineer@gmail.com',
                    'password' => bcrypt('password'),
                ],
                 [
                    'id' => 9,
                    'name' => 'managing director',
                    'email' => 'managingdirector@gmail.com',
                    'password' => bcrypt('password'),
                ],
            ];

        foreach ($users as $user) {
            \App\Models\User::updateOrCreate(
                ['id' => $user['id']],
                $user
            );
        }
    }
}


