<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

/**
 * Demo data mirroring the mockup (ui-reference/Suppliers.html) so the page has
 * something to render before the purchase modules are wired up.
 *
 * NOTE: balance + invoice counts in the UI come from the purchase ledger
 * (purchase_payables / purchase_invoices). This seeder only fills the
 * suppliers table — until those tables exist, balance shows ৳ 0 / settled
 * and invoice counts show 0. That's expected.
 */
class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['name' => 'Meghna Cement & Aggregates Ltd.', 'contact_person' => 'Rafiqul Islam',  'phone' => '+880 1711 904 220', 'alternate_phone' => '+880 2 9551 7720', 'email' => 'accounts@meghnacement.com.bd', 'address' => 'Plot 14, Tongi I/A, Gazipur',   'status' => true,  'is_blocked' => false],
            ['name' => 'BSRM Steel Distribution',          'contact_person' => 'Nasir Uddin',    'phone' => '+880 1819 332 014', 'alternate_phone' => null,                'email' => 'sales.dhaka@bsrm.com',        'address' => 'Motijheel C/A, Dhaka 1000',    'status' => true,  'is_blocked' => false],
            ['name' => 'Shah Cement Hardware',             'contact_person' => 'Kamrul Hasan',   'phone' => '+880 1715 661 909', 'alternate_phone' => '+880 1911 220 884', 'email' => 'shahcement.hw@gmail.com',     'address' => 'Pagla, Narayanganj',           'status' => true,  'is_blocked' => false],
            ['name' => 'Akij Ceramics & Tiles',            'contact_person' => 'Sultana Razia',  'phone' => '+880 1670 118 552', 'alternate_phone' => null,                'email' => 'b2b@akijceramics.com',        'address' => 'Tejgaon I/A, Dhaka',           'status' => true,  'is_blocked' => false],
            ['name' => 'Rangs Electric Supplies',          'contact_person' => 'Tanvir Ahmed',   'phone' => '+880 1842 700 311', 'alternate_phone' => null,                'email' => 'trade@rangsgroup.com',        'address' => 'Gulshan 1, Dhaka 1212',        'status' => true,  'is_blocked' => false],
            ['name' => 'National Paints Trading',          'contact_person' => 'Habibur Rahman', 'phone' => '+880 1556 220 047', 'alternate_phone' => '+880 31 651 220',   'email' => 'info@nationalpaints.bd',      'address' => 'Agrabad C/A, Chattogram',      'status' => false, 'is_blocked' => false],
            ['name' => 'Partex Plywood & Boards',          'contact_person' => 'Mizanur Rahman', 'phone' => '+880 1733 880 145', 'alternate_phone' => null,                'email' => 'sales@partexbd.com',          'address' => 'Rupganj, Narayanganj',         'status' => true,  'is_blocked' => false],
            ['name' => 'Crown Cement Depot',               'contact_person' => 'Abdul Mannan',   'phone' => '+880 1611 540 778', 'alternate_phone' => null,                'email' => null,                          'address' => 'Keraniganj, Dhaka',            'status' => true,  'is_blocked' => true],
            ['name' => 'Aftab Sanitary Wares',             'contact_person' => 'Jahanara Begum', 'phone' => '+880 1990 117 663', 'alternate_phone' => '+880 2 8831 009',   'email' => 'order@aftabsanitary.com',     'address' => 'Mirpur 1, Dhaka 1216',         'status' => true,  'is_blocked' => false],
        ];

        foreach ($rows as $row) {
            Supplier::create($row + [
                'trade_license_no' => 'TRAD/2024/' . random_int(1000, 9999),
                'documents'        => [],   // file IDs only
            ]);
        }
    }
}
