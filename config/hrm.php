<?php

return [
    /*
    |--------------------------------------------------------------------------
    | HRM Account Mapping
    |--------------------------------------------------------------------------
    |
    | Configure the Chart of Accounts mapping used by HRM journal posting.
    | Code lookup is tried first, then fallback by account name.
    |
    */
    'accounts' => [
        'salary_expense' => [
            'code' => env('HRM_ACCOUNT_SALARY_EXPENSE_CODE'),
            'name' => env('HRM_ACCOUNT_SALARY_EXPENSE_NAME', 'Salary Expense'),
        ],
        'salary_payable' => [
            'code' => env('HRM_ACCOUNT_SALARY_PAYABLE_CODE'),
            'name' => env('HRM_ACCOUNT_SALARY_PAYABLE_NAME', 'Salary Payable'),
        ],
        'employee_advance' => [
            'code' => env('HRM_ACCOUNT_EMPLOYEE_ADVANCE_CODE'),
            'name' => env('HRM_ACCOUNT_EMPLOYEE_ADVANCE_NAME', 'Employee Advance'),
        ],
        'cash' => [
            'code' => env('HRM_ACCOUNT_CASH_CODE'),
            'name' => env('HRM_ACCOUNT_CASH_NAME', 'Cash'),
        ],
        'bank' => [
            'code' => env('HRM_ACCOUNT_BANK_CODE'),
            'name' => env('HRM_ACCOUNT_BANK_NAME', 'Bank'),
        ],
    ],

    'payment_method_account' => [
        'cash' => 'cash',
        'bank' => 'bank',
        'cheque' => 'bank',
        'mobile_banking' => 'bank',
    ],

    'defaults' => [
        'advance_payment_method' => 'cash',
    ],
];

