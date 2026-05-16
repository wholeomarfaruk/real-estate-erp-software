<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Property;
use App\Models\PropertyFloor;
use App\Models\PropertyUnit;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
    public function run(): void
    {
        // ── Project ──────────────────────────────────────────────────────────
        $project = Project::updateOrCreate(
            ['code' => 'SUR-2024'],
            [
                'name'         => 'Star Unity Residencia',
                'code'         => 'SUR-2024',
                'project_type' => 'residential',
                'location'     => 'Bashundhara R/A, Dhaka',
                'start_date'   => '2024-01-01',
                'end_date'     => '2026-12-31',
                'budget'       => 15000000,
                'status'       => 'ongoing',
                'description'  => 'Premium residential apartment project in Bashundhara R/A.',
            ]
        );

        // ── Property 1: 10-storey apartment building ─────────────────────────
        $p1 = Property::updateOrCreate(
            ['code' => 'SUR-A'],
            [
                'project_id'    => $project->id,
                'name'          => 'Residencia Tower A',
                'code'          => 'SUR-A',
                'address'       => 'Block C, Road 12, Bashundhara R/A, Dhaka',
                'type'          => 'apartment',
                'total_area'    => 12500.00,
                'land_size'     => 3200.00,
                'status'        => 'active',
                'registered_at' => '2024-03-15',
                'remarks'       => '10-storey residential building with 4 units per floor.',
            ]
        );

        // Floors for Property 1
        $floorsP1 = [];
        $floorLabels = [
            1 => 'Ground Floor',
            2 => '1st Floor',
            3 => '2nd Floor',
            4 => '3rd Floor',
            5 => '4th Floor',
            6 => '5th Floor',
        ];
        foreach ($floorLabels as $order => $label) {
            $floorsP1[$order] = PropertyFloor::updateOrCreate(
                ['code' => 'F' . str_pad($order, 2, '0', STR_PAD_LEFT)],
                [
                    'property_id' => $p1->id,
                    'code'        => 'F' . str_pad($order, 2, '0', STR_PAD_LEFT),
                    'label'       => $label,
                    'sort_order'  => $order,
                    'floor_area'  => 2000.00,
                ]
            );
        }

        // Units for Property 1 — 4 flats per floor (except ground = 2 shops + 2 flats)
        $unitStatus = ['available', 'available', 'booked', 'sold'];
        foreach ($floorsP1 as $order => $floor) {
            $types   = $order === 1
                ? [['shop', 'A'], ['shop', 'B'], ['flat', 'C'], ['flat', 'D']]
                : [['flat', 'A'], ['flat', 'B'], ['flat', 'C'], ['flat', 'D']];

            foreach ($types as $i => [$type, $suffix]) {
                PropertyUnit::updateOrCreate(
                    ['code' => 'SUR-A-' . $order . $suffix],
                    [
                        'property_id'       => $p1->id,
                        'property_floor_id' => $floor->id,
                        'code'              => 'SUR-A-' . $order . $suffix,
                        'type'              => $type,
                        'status'            => $unitStatus[$i],
                        'area'              => $type === 'shop' ? 450.00 : 1250.00,
                        'price'             => $type === 'shop' ? 3500000.00 : 6500000.00,
                        'service_charge'    => $type === 'shop' ? 3000.00 : 5000.00,
                        'facing'            => ['north', 'south', 'east', 'west'][$i],
                        'sort_order'        => $i + 1,
                    ]
                );
            }
        }

        // ── Property 2: Commercial building ──────────────────────────────────
        $p2 = Property::updateOrCreate(
            ['code' => 'SCP-01'],
            [
                'project_id'    => $project->id,
                'name'          => 'Commerce Plaza',
                'code'          => 'SCP-01',
                'address'       => 'Plot 5, Road 7, Gulshan 2, Dhaka',
                'type'          => 'commercial',
                'total_area'    => 8000.00,
                'land_size'     => 2400.00,
                'status'        => 'active',
                'registered_at' => '2024-06-01',
                'remarks'       => '6-storey commercial building with shops and office spaces.',
            ]
        );

        // Floors for Property 2
        $floorsP2 = [];
        $commercialFloors = [
            1 => 'Ground Floor',
            2 => '1st Floor',
            3 => '2nd Floor',
            4 => '3rd Floor',
            5 => '4th Floor',
            6 => '5th Floor',
        ];
        foreach ($commercialFloors as $order => $label) {
            $floorsP2[$order] = PropertyFloor::updateOrCreate(
                ['code' => 'F' . str_pad($order, 2, '0', STR_PAD_LEFT)],
                [
                    'property_id' => $p2->id,
                    'code'        => 'F' . str_pad($order, 2, '0', STR_PAD_LEFT),
                    'label'       => $label,
                    'sort_order'  => $order,
                    'floor_area'  => 1200.00,
                ]
            );
        }

        // Units for Property 2 — shops on ground, offices above
        $shopStatus   = ['available', 'sold', 'booked', 'available', 'available', 'sold'];
        $officeStatus = ['available', 'available', 'booked', 'available'];

        foreach ($floorsP2 as $order => $floor) {
            if ($order === 1) {
                // Ground: 6 shops
                for ($s = 1; $s <= 6; $s++) {
                    PropertyUnit::updateOrCreate(
                        ['code' => 'SCP-G' . str_pad($s, 2, '0', STR_PAD_LEFT)],
                        [
                            'property_id'       => $p2->id,
                            'property_floor_id' => $floor->id,
                            'code'              => 'SCP-G' . str_pad($s, 2, '0', STR_PAD_LEFT),
                            'type'              => 'shop',
                            'status'            => $shopStatus[$s - 1],
                            'area'              => 350.00,
                            'price'             => 4200000.00,
                            'service_charge'    => 4000.00,
                            'sort_order'        => $s,
                        ]
                    );
                }
            } else {
                // Upper floors: 4 office units
                $letters = ['A', 'B', 'C', 'D'];
                foreach ($letters as $i => $letter) {
                    PropertyUnit::updateOrCreate(
                        ['code' => 'SCP-' . $order . $letter],
                        [
                            'property_id'       => $p2->id,
                            'property_floor_id' => $floor->id,
                            'code'              => 'SCP-' . $order . $letter,
                            'type'              => 'office',
                            'status'            => $officeStatus[$i],
                            'area'              => 700.00,
                            'price'             => 8500000.00,
                            'service_charge'    => 7000.00,
                            'facing'            => ['north', 'south', 'east', 'west'][$i],
                            'sort_order'        => $i + 1,
                        ]
                    );
                }
            }
        }
    }
}
