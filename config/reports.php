<?php

/**
 * Reports Configuration
 *
 * Define all available reports in the system.
 * Single source of truth for report metadata, services, and components.
 *
 * Structure:
 * 'category' => [
 *     'name' => 'Display name',
 *     'description' => 'Category description',
 *     'icon' => 'icon-name',
 *     'reports' => [
 *         'report-slug' => [
 *             'title' => 'Report title',
 *             'description' => 'Report description',
 *             'service' => ServiceClass::class,
 *             'component' => ComponentClass::class,
 *             'view' => 'view.path.to.component',
 *             'permission' => 'permission.name',
 *         ],
 *     ],
 * ]
 */

return [
    'finance' => [
        'name' => 'Finance Reports',
        'description' => 'Bank statements, daily reports, cash flows, and financial summaries.',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>',
        'reports' => [
            'daily-statement' => [
                'title' => 'Daily Statement',
                'description' => 'Bank accounts ledger, receipts, and payments for a single day.',
                'service' => App\Services\Reports\Finance\DailyStatementService::class,
                'component' => App\Livewire\Admin\Reports\Finance\DailyStatement::class,
                'view' => 'livewire.admin.reports.finance.daily-statement',
                'permission' => 'reports.finance.daily-statement.view',
            ],
        ],
    ],

    'sales' => [
        'name' => 'Sales & Rents Reports',
        'description' => 'Complete picture of bookings, leases, cancellations and salesperson achievement.',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
        'reports' => [
            'regular-client-statement' => [
                'title' => 'Regular Client Statement',
                'description' => 'All clients with outstanding balances regardless of overdue status.',
                'service' => App\Services\Reports\Sales\RegularClientStatementService::class,
                'component' => App\Livewire\Admin\Reports\Sales\RegularClientStatement::class,
                'view' => 'livewire.admin.reports.sales.regular-client-statement',
                'permission' => 'reports.sales.regular-client-statement.view',
            ],

            'client-wise-statement' => [
                'title' => 'Client Wise Statement',
                'description' => 'Detailed sales and rent transactions for a specific client.',
                'service' => App\Services\Reports\Sales\ClientWiseStatementService::class,
                'component' => App\Livewire\Admin\Reports\Sales\ClientWiseStatement::class,
                'view' => 'livewire.admin.reports.sales.client-wise-statement',
                'permission' => 'reports.sales.client-wise-statement.view',
            ],

            'overdue-client-statement' => [
                'title' => 'Overdue Client Statement',
                'description' => 'Clients with one or more overdue installments.',
                'service' => App\Services\Reports\Sales\OverdueClientStatementService::class,
                'component' => App\Livewire\Admin\Reports\Sales\OverdueClientStatement::class,
                'view' => 'livewire.admin.reports.sales.overdue-client-statement',
                'permission' => 'reports.sales.overdue-client-statement.view',
            ],

            'classified-client-statement' => [
                'title' => 'Classified Client Statement',
                'description' => 'High-risk clients with more than 3 overdue installments requiring collection action.',
                'service' => App\Services\Reports\Sales\ClassifiedClientStatementService::class,
                'component' => App\Livewire\Admin\Reports\Sales\ClassifiedClientStatement::class,
                'view' => 'livewire.admin.reports.sales.classified-client-statement',
                'permission' => 'reports.sales.classified-client-statement.view',
            ],

            // 'detailed-client-statement' => [
            //     'title' => 'Detailed Client Statement',
            //     'description' => 'Single client ledger with full payment schedule and history.',
            //     'service' => App\Services\Reports\Sales\DetailedClientStatementService::class,
            //     'component' => App\Livewire\Admin\Reports\Sales\DetailedClientStatement::class,
            //     'view' => 'livewire.admin.reports.sales.detailed-client-statement',
            //     'permission' => 'reports.sales.detailed-client-statement.view',
            // ],

            'all-client-statement' => [
                'title' => 'All Client Statement',
                'description' => 'Master statement of all clients with outstanding balances — total, paid, outstanding, overdue amount, scheduled & overdue installments.',
                'service' => App\Services\Reports\Sales\AllClientStatementService::class,
                'component' => App\Livewire\Admin\Reports\Sales\AllClientStatement::class,
                'view' => 'livewire.admin.reports.sales.all-client-statement',
                'permission' => 'reports.sales.all-client-statement.view',
            ],

            // 'upcoming-installments' => [
            //     'title' => 'Upcoming Installments',
            //     'description' => 'Installments due within the next 7, 15, or 30 days.',
            //     'service' => App\Services\Reports\Sales\UpcomingInstallmentsService::class,
            //     'component' => App\Livewire\Admin\Reports\Sales\UpcomingInstallments::class,
            //     'view' => 'livewire.admin.reports.sales.upcoming-installments',
            //     'permission' => 'reports.sales.upcoming-installments.view',
            // ],

            // 'collection-performance' => [
            //     'title' => 'Collection Performance',
            //     'description' => 'Monthly collection target vs. actual with performance metrics.',
            //     'service' => App\Services\Reports\Sales\CollectionPerformanceService::class,
            //     'component' => App\Livewire\Admin\Reports\Sales\CollectionPerformance::class,
            //     'view' => 'livewire.admin.reports.sales.collection-performance',
            //     'permission' => 'reports.sales.collection-performance.view',
            // ],

            // 'defaulter-report' => [
            //     'title' => 'Defaulter Report',
            //     'description' => 'Clients with no payment received in the last X months.',
            //     'service' => App\Services\Reports\Sales\DefaulterReportService::class,
            //     'component' => App\Livewire\Admin\Reports\Sales\DefaulterReport::class,
            //     'view' => 'livewire.admin.reports.sales.defaulter-report',
            //     'permission' => 'reports.sales.defaulter-report.view',
            // ],

            // 'aging-outstanding' => [
            //     'title' => 'Aging Outstanding',
            //     'description' => 'Outstanding amounts grouped into age buckets (0-30, 31-60, 61-90, etc.).',
            //     'service' => App\Services\Reports\Sales\AgingOutstandingService::class,
            //     'component' => App\Livewire\Admin\Reports\Sales\AgingOutstanding::class,
            //     'view' => 'livewire.admin.reports.sales.aging-outstanding',
            //     'permission' => 'reports.sales.aging-outstanding.view',
            // ],

            // 'rent-collection' => [
            //     'title' => 'Rent Collection Report',
            //     'description' => 'Tenant rent status, payment history, and collection metrics.',
            //     'service' => App\Services\Reports\Sales\RentCollectionService::class,
            //     'component' => App\Livewire\Admin\Reports\Sales\RentCollection::class,
            //     'view' => 'livewire.admin.reports.sales.rent-collection',
            //     'permission' => 'reports.sales.rent-collection.view',
            // ],
        ],
    ],
];
