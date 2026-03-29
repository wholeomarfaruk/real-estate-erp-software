<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'superadmin',
            'admin',
            'user',
        ];
        foreach ($roles as $role) {
            // Role::create(['name' => $role]);
            Role::updateOrCreate(['name' => $role]);
        }

        //permissions
        $permissions = [
            //user
            ['id' => 1, 'name' => 'user.show'],
            ['id' => 2, 'name' => 'user.view'],
            ['id' => 3, 'name' => 'user.create'],
            ['id' => 4, 'name' => 'user.edit'],
            ['id' => 5, 'name' => 'user.delete'],
            ['id' => 6, 'name' => 'user.role_assign'],
            ['id' => 7, 'name' => 'user.role_remove'],

            //permissions
            ['id' => 8, 'name' => 'permission.show'],
            ['id' => 9, 'name' => 'permission.view'],
            ['id' => 10, 'name' => 'permission.create'],
            ['id' => 11, 'name' => 'permission.edit'],
            ['id' => 12, 'name' => 'permission.delete'],

            //roles
            ['id' => 13, 'name' => 'role.view'],
            ['id' => 14, 'name' => 'role.create'],
            ['id' => 15, 'name' => 'role.edit'],
            ['id' => 16, 'name' => 'role.delete'],

            //panel
            ['id' => 17, 'name' => 'panel.show'],
            ['id' => 18, 'name' => 'panel.view'],

            //dashboard
            ['id' => 19, 'name' => 'dashboard.readonly'],
            ['id' => 20, 'name' => 'dashboard.view'],

            //UI components
            ['id' => 21, 'name' => 'ui.show'],
            ['id' => 22, 'name' => 'ui_components.view'],

            //projects
            ['id' => 23, 'name' => 'project.view'],
            ['id' => 24, 'name' => 'project.create'],
            ['id' => 25, 'name' => 'project.edit'],
            ['id' => 26, 'name' => 'project.delete'],

        ];
        foreach ($permissions as $permission) {
            // Permission::create(['name' => $permission]);
            Permission::updateOrCreate(
                ['id' => $permission['id'] ?? null],
                ['name' => $permission['name'] ?? null]
            );
        }
      
    }
}
