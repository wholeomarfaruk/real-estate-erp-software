<?php

return [

    'total_sales' => [
        'label' => 'Total Sales',
        'component' => \App\Livewire\Admin\Dashboard\TotalSalesWidget::class,
        'roles' => ['superadmin','admin', 'accounts', 'md', 'chairman'],
    ],

    'total_expense' => [
        'label' => 'Total Expense',
        'component' => \App\Livewire\Admin\Dashboard\TotalExpenseWidget::class,
        'roles' => ['superadmin','admin', 'accounts'],
    ],

    'stock_summary' => [
        'label' => 'Stock Summary',
        'component' => \App\Livewire\Admin\Dashboard\StockSummaryWidget::class,
        'roles' => ['superadmin','admin', 'storemanager'],
    ],

    'project_status' => [
        'label' => 'Project Status',
        'component' => \App\Livewire\Admin\Dashboard\ProjectStatusWidget::class,
        'roles' => ['superadmin', 'admin', 'chiefengineer', 'md'],
    ],

];