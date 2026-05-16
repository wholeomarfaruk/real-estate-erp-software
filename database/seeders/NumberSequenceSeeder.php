<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NumberSequenceSeeder extends Seeder
{
    public function run(): void
    {
        // Parse max numeric values from existing formatted strings so re-running
        // this seeder on a live database never resets a counter below its real max.
        $frMax = (int) DB::table('payments')
            ->where('payment_no', 'like', 'FR-%')
            ->selectRaw('MAX(CAST(SUBSTRING(payment_no, 4) AS UNSIGNED)) as n')
            ->value('n');

        $piMax = (int) DB::table('purchase_invoices')
            ->where('invoice_no', 'like', 'PI-%')
            ->selectRaw('MAX(CAST(SUBSTRING(invoice_no, 4) AS UNSIGNED)) as n')
            ->value('n');

        $piPmtMax = (int) DB::table('payments')
            ->where('payment_no', 'like', 'PI-PMT-%')
            ->selectRaw('MAX(CAST(SUBSTRING(payment_no, 8) AS UNSIGNED)) as n')
            ->value('n');

        $poMax = (int) DB::table('purchase_orders')
            ->where('po_no', 'like', 'PO-%')
            ->selectRaw('MAX(CAST(SUBSTRING(po_no, 4) AS UNSIGNED)) as n')
            ->value('n');

        $srMax = (int) DB::table('stock_receives')
            ->where('receive_no', 'like', 'SR-%')
            ->selectRaw('MAX(CAST(SUBSTRING(receive_no, 4) AS UNSIGNED)) as n')
            ->value('n');

        $sequences = [
            ['prefix' => 'PO',     'last_number' => $poMax,    'padding' => 6],
            ['prefix' => 'SR',     'last_number' => $srMax,    'padding' => 6],
            ['prefix' => 'PI',     'last_number' => $piMax,    'padding' => 6],
            ['prefix' => 'FR',     'last_number' => $frMax,    'padding' => 6],
            ['prefix' => 'PI-PMT', 'last_number' => $piPmtMax, 'padding' => 6],
            ['prefix' => 'PAY',    'last_number' => 0,         'padding' => 6],
        ];

        foreach ($sequences as $seq) {
            // Insert only if the row doesn't exist yet.
            // To force a re-sync on an existing installation, run:
            //   DB::table('number_sequences')->truncate(); php artisan db:seed --class=NumberSequenceSeeder
            DB::table('number_sequences')->insertOrIgnore([
                'prefix'      => $seq['prefix'],
                'last_number' => $seq['last_number'],
                'padding'     => $seq['padding'],
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }
}
