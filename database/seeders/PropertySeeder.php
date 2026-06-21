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
        // Get or create a default project for properties
        $project = Project::firstOrCreate(
            ['code' => 'DEFAULT-PROP'],
            [
                'name'         => 'Default Properties Project',
                'code'         => 'DEFAULT-PROP',
                'project_type' => ['residential', 'commercial'],
                'location'     => 'Dhaka',
                'status'       => 'ongoing',
            ]
        );

        // ── Property 1: 6-storey apartment building ──────────────────────────
        $p1 = Property::updateOrCreate(
            ['code' => 'PROP-001'],
            [
                'project_id'     => $project->id,
                'name'           => 'Residencia Tower A',
                'code'           => 'PROP-001',
                'address'        => 'Block C, Road 12, Bashundhara R/A, Dhaka',
                'type'           => 'residential',
                'total_area'     => 12500.00,
                'land_size'      => 3200.00,
                'status'         => 'active',
                'registered_at'  => '2024-03-15',
                'remarks'        => '6-storey residential building with 4 units per floor.',
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
                ['property_id' => $p1->id, 'code' => 'F' . str_pad($order, 2, '0', STR_PAD_LEFT)],
                [
                    'label'       => $label,
                    'sort_order'  => $order,
                    'floor_area'  => 2000.00,
                ]
            );
        }

        // Units for Property 1 — 4 units per floor
        $unitStatus = ['available', 'available', 'booked', 'sold'];
        foreach ($floorsP1 as $order => $floor) {
            for ($u = 0; $u < 4; $u++) {
                $suffix = chr(65 + $u); // A, B, C, D
                PropertyUnit::updateOrCreate(
                    ['code' => 'PROP-001-' . $order . $suffix],
                    [
                        'property_id'       => $p1->id,
                        'property_floor_id' => $floor->id,
                        'code'              => 'PROP-001-' . $order . $suffix,
                        'type'              => 'flat',
                        'status'            => $unitStatus[$u],
                        'area'              => 1250.00,
                        'price'             => 6500000.00,
                        'service_charge'    => 5000.00,
                        'facing'            => ['north', 'south', 'east', 'west'][$u],
                        'sort_order'        => $u + 1,
                        'purpose'           => 'sell',
                        'down_payment_percentage' => 20.00,
                    ]
                );
            }
        }

        // ── Property 2: 5-storey commercial building ─────────────────────────
        $p2 = Property::updateOrCreate(
            ['code' => 'PROP-002'],
            [
                'project_id'     => $project->id,
                'name'           => 'Commerce Plaza',
                'code'           => 'PROP-002',
                'address'        => 'Plot 5, Road 7, Gulshan 2, Dhaka',
                'type'           => 'commercial',
                'total_area'     => 8000.00,
                'land_size'      => 2400.00,
                'status'         => 'active',
                'registered_at'  => '2024-06-01',
                'remarks'        => '5-storey commercial building with shops and offices.',
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
        ];
        foreach ($commercialFloors as $order => $label) {
            $floorsP2[$order] = PropertyFloor::updateOrCreate(
                ['property_id' => $p2->id, 'code' => 'F' . str_pad($order, 2, '0', STR_PAD_LEFT)],
                [
                    'label'       => $label,
                    'sort_order'  => $order,
                    'floor_area'  => 1200.00,
                ]
            );
        }

        // Units for Property 2 — shops on ground, offices on upper floors
        $shopStatus   = ['available', 'sold', 'booked', 'available', 'available', 'sold'];
        $officeStatus = ['available', 'available', 'booked', 'available'];

        foreach ($floorsP2 as $order => $floor) {
            if ($order === 1) {
                // Ground floor: 6 shops
                for ($s = 0; $s < 6; $s++) {
                    PropertyUnit::updateOrCreate(
                        ['code' => 'PROP-002-G' . str_pad($s + 1, 2, '0', STR_PAD_LEFT)],
                        [
                            'property_id'       => $p2->id,
                            'property_floor_id' => $floor->id,
                            'code'              => 'PROP-002-G' . str_pad($s + 1, 2, '0', STR_PAD_LEFT),
                            'type'              => 'shop',
                            'status'            => $shopStatus[$s],
                            'area'              => 350.00,
                            'price'             => 4200000.00,
                            'service_charge'    => 4000.00,
                            'sort_order'        => $s + 1,
                            'purpose'           => 'rent',
                            'deposit_amount'    => 200000.00,
                        ]
                    );
                }
            } else {
                // Upper floors: 4 offices each
                for ($o = 0; $o < 4; $o++) {
                    $suffix = chr(65 + $o); // A, B, C, D
                    PropertyUnit::updateOrCreate(
                        ['code' => 'PROP-002-' . $order . $suffix],
                        [
                            'property_id'       => $p2->id,
                            'property_floor_id' => $floor->id,
                            'code'              => 'PROP-002-' . $order . $suffix,
                            'type'              => 'office',
                            'status'            => $officeStatus[$o],
                            'area'              => 700.00,
                            'price'             => 8500000.00,
                            'service_charge'    => 7000.00,
                            'facing'            => ['north', 'south', 'east', 'west'][$o],
                            'sort_order'        => $o + 1,
                            'purpose'           => 'rent',
                            'deposit_amount'    => 150000.00,
                        ]
                    );
                }
            }
        }
    }
}
