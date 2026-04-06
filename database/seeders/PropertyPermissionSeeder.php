<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PropertyPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'property.view',
            'property.create',
            'property.edit',
            'property.delete',

            'property.floor.view',
            'property.floor.create',
            'property.floor.edit',
            'property.floor.delete',

            'property.unit.view',
            'property.unit.create',
            'property.unit.edit',
            'property.unit.delete',
        ];

        foreach ($permissions as $name) {
            Permission::updateOrCreate(['name' => $name], ['name' => $name]);
        }
    }
}
