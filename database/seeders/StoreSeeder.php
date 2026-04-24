<?php

namespace Database\Seeders;

use App\Enums\Inventory\StoreType;
use App\Enums\Project\Status as ProjectStatus;
use App\Enums\Project\Type as ProjectType;
use App\Models\Project;
use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $projectA = Project::query()->firstOrCreate(
            ['code' => 'SUDP1'],
            [
                'name' => 'Sample Project One',
                'project_type' => ProjectType::RESIDENTIAL->value,
                'status' => ProjectStatus::ONGOING->value,
                'location' => 'Dhaka',
                 'start_date' => now()->addDays(30),
                'end_date' => now()->addDays(365),
            ]
        );

        $projectB = Project::query()->firstOrCreate(
            ['code' => 'SUDP2'],
            [
                'name' => 'Sample Project Two',
                'project_type' => ProjectType::COMMERCIAL->value,
                'status' => ProjectStatus::UPCOMING->value,
                'location' => 'Chattogram',
                'start_date' => now()->addDays(30),
                'end_date' => now()->addDays(365),
            ]
        );

        $stores = [
            [
                'name' => 'Bashundhara Office Store',
                'code' => 'ST-OFF-001',
                'type' => StoreType::OFFICE->value,
                'project_id' => null,
                'address' => 'Bashundhara, Dhaka',
                'description' => 'Main office warehouse for incoming materials.',
                'status' => true,
            ],
            [
                'name' => 'Motijheel Office Store',
                'code' => 'ST-OFF-002',
                'type' => StoreType::OFFICE->value,
                'project_id' => null,
                'address' => 'Motijheel, Dhaka',
                'description' => 'Secondary office warehouse.',
                'status' => true,
            ],
            [
                'name' => 'Project Store - One',
                'code' => 'ST-PRJ-001',
                'type' => StoreType::PROJECT->value,
                'project_id' => $projectA->id,
                'address' => 'Project Site One',
                'description' => 'Project-specific store for site one.',
                'status' => true,
            ],
            [
                'name' => 'Project Store - Two',
                'code' => 'ST-PRJ-002',
                'type' => StoreType::PROJECT->value,
                'project_id' => $projectB->id,
                'address' => 'Project Site Two',
                'description' => 'Project-specific store for site two.',
                'status' => true,
            ],
        ];

        foreach ($stores as $store) {
            Store::query()->updateOrCreate(
                ['code' => $store['code']],
                $store
            );
        }
    }
}
