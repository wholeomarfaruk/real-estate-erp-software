<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'))->name('home');
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return redirect()->route('admin.dashboard');
    })->name('dashboard');
});
Route::get('/projects/properties', function () {
    return "test";
})->name('projects.properties');

Route::get('/test/sales-report-pdf', function () {
    $report = [
        'title'   => 'Regular Clients Statement',
        'slug'    => 'regular-client-statement',
        'meta'    => [
            'company_name' => 'Star Unity Development Ltd.',
            'generated_at' => now()->format('d-M-Y H:i A'),
            'generated_by' => 'Test User',
            'from_date'    => '01 Jan 2025',
            'to_date'      => '31 Dec 2025',
            'file_name'    => 'regular-clients-statement-' . now()->format('Y-m-d') . '.pdf',
            'notes'        => 'This is a test statement. Outstanding balances in bold remain payable as of the statement date.',
        ],
        'columns' => [
            ['key' => 'client_name',          'label' => 'Client Name',      'align' => 'left'],
            ['key' => 'sale_property_count',  'label' => 'Sale Properties',  'align' => 'center'],
            ['key' => 'rent_property_count',  'label' => 'Rent Properties',  'align' => 'center'],
            ['key' => 'total_paid',           'label' => 'Total Paid',       'align' => 'right'],
            ['key' => 'total_due',            'label' => 'Total Outstanding','align' => 'right'],
        ],
        'rows' => [
            [
                'customer_id'       => 1,
                'client_display_id' => 'CUST-0000001',
                'client_name'       => 'John Doe',
                'sale_property_count'   => 1,
                'rent_property_count'   => 0,
                'total_paid'        => 2500000,
                'total_due'         => 2500000,
            ],
            [
                'customer_id'       => 2,
                'client_display_id' => 'CUST-0000002',
                'client_name'       => 'Jane Smith',
                'sale_property_count'   => 1,
                'rent_property_count'   => 0,
                'total_paid'        => 3200000,
                'total_due'         => 0,
            ],
            [
                'customer_id'       => 3,
                'client_display_id' => 'CUST-0000003',
                'client_name'       => 'Rahim Uddin',
                'sale_property_count'   => 1,
                'rent_property_count'   => 1,
                'total_paid'        => 1000000,
                'total_due'         => 6500000,
            ],
        ],
        'summary' => [
            'total_clients'      => 3,
            'total_paid'         => 6700000,
            'total_outstanding'  => 9000000,
        ],
    ];

    return view('pdf.reports.sales.exports.report-pdf', compact('report'));
})->name('test.sales-report-pdf');
