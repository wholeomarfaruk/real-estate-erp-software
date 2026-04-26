<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Inventory / Purchase
    |--------------------------------------------------------------------------
    */
    'purchase_order' => [
        'label' => 'Purchase Order',
        'model' => App\Models\PurchaseOrder::class,
        'multiple' => false,
    ],

    'stock_receive' => [
        'label' => 'Stock Receive',
        'model' => App\Models\StockReceive::class,
        'multiple' => false,
    ],

    'supplier' => [
        'label' => 'Supplier',
        'model' => App\Models\Supplier::class,
        'multiple' => false,
    ],

    'supplier_return' => [
        'label' => 'Supplier Return',
        'model' => App\Models\SupplierReturn::class,
        'multiple' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Accounts / Expense / Payment
    |--------------------------------------------------------------------------
    */
    'expense' => [
        'label' => 'Expense',
        'model' => App\Models\Expense::class,
        'multiple' => false,
    ],

    'transaction' => [
        'label' => 'Transaction',
        'model' => App\Models\Transaction::class,
        'multiple' => false,
    ],

    'purchase_payable' => [
        'label' => 'Purchase Payable',
        'model' => App\Models\PurchasePayable::class,
        'multiple' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | HRM / Salary
    |--------------------------------------------------------------------------
    */
    'payroll' => [
        'label' => 'Payroll',
        'model' => App\Models\Payroll::class,
        'multiple' => true,
    ],

    'advance_salary' => [
        'label' => 'Advance Salary',
        'model' => App\Models\EmployeeAdvance::class,
        'multiple' => true,
    ],

    'Payroll' => [
        'label' => 'Employee',
        'model' => App\Models\Payroll::class,
        'multiple' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Project / Construction
    |--------------------------------------------------------------------------
    */
    'project' => [
        'label' => 'Project',
        'model' => App\Models\Project::class,
        'multiple' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Property / Sales / Rent
    |--------------------------------------------------------------------------
    */
    'property' => [
        'label' => 'Property',
        'model' => App\Models\Property::class,
        'multiple' => false,
    ],

    // 'customer' => [
    //     'label' => 'Customer',
    //     'model' => App\Models\Customer::class,
    //     'multiple' => false,
    // ],

];
