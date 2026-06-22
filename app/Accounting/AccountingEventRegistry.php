<?php

namespace App\Accounting;

use App\Enums\Accounts\TransactionType;

/**
 * Code-owned catalog of the business events that can post to the ledger.
 *
 * Events are NOT admin-creatable: their keys, modules, transaction types and the
 * set of runtime "slots" they expose live here, so the data-driven posting rules
 * (which accounts each leg uses, and the Dr/Cr sides) can be edited safely in the
 * admin UI without ever inventing an unknown or unbalanced event.
 *
 * Each event also declares a `default_rules` recipe used by the seeder; admins can
 * re-point the fixed legs afterwards. Fixed legs reference an account by its stable
 * chart-of-accounts `code` (see ChartOfAccountsSeeder).
 */
class AccountingEventRegistry
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            'property.down_payment' => [
                'module'           => 'property',
                'name'             => 'Property — Sale',
                'description'      => 'Customer pays an advance against a property sale. Dr the received payment account, Cr Customer Advance.',
                'transaction_type' => TransactionType::INCOME->value,
                'runtime_slots'    => ['payment_account' => 'Payment Account (Cash/Bank/MFS)'],
                'default_rules'    => [
                    ['leg' => 'debit',  'account_source' => 'runtime', 'runtime_slot' => 'payment_account', 'description' => 'Payment received'],
                    ['leg' => 'credit', 'account_source' => 'fixed',   'account_code' => 'LIAB-ADV',        'description' => 'Customer advance'],
                ],
            ],
            'property.handover' => [
                'module'           => 'property',
                'name'             => 'Property — Handover (Revenue Recognition)',
                'description'      => 'On handover the advance is recognised as sales revenue. Dr Customer Advance, Cr Property Sales Revenue.',
                'transaction_type' => TransactionType::INCOME->value,
                'runtime_slots'    => [],
                'default_rules'    => [
                    ['leg' => 'debit',  'account_source' => 'fixed', 'account_code' => 'LIAB-ADV',  'description' => 'Customer advance'],
                    ['leg' => 'credit', 'account_source' => 'fixed', 'account_code' => 'INC-SALES', 'description' => 'Property sales revenue'],
                ],
            ],
            'property.rent_collection' => [
                'module'           => 'property',
                'name'             => 'Property — Rent Collection',
                'description'      => 'Rent received from a tenant. Dr the received payment account, Cr Rent Revenue.',
                'transaction_type' => TransactionType::INCOME->value,
                'runtime_slots'    => ['payment_account' => 'Payment Account (Cash/Bank/MFS)'],
                'default_rules'    => [
                    ['leg' => 'debit',  'account_source' => 'runtime', 'runtime_slot' => 'payment_account', 'description' => 'Rent received'],
                    ['leg' => 'credit', 'account_source' => 'fixed',   'account_code' => 'INC-RENT',        'description' => 'Rent revenue'],
                ],
            ],
            'expense.payment' => [
                'module'           => 'expense',
                'name'             => 'Expense — Payment',
                'description'      => 'An expense is paid. Dr the expense account, Cr the payment account the money leaves from.',
                'transaction_type' => TransactionType::EXPENSE->value,
                'runtime_slots'    => ['payment_account' => 'Payment Account (Cash/Bank/MFS)'],
                'default_rules'    => [
                    ['leg' => 'debit',  'account_source' => 'fixed',   'account_code' => 'EXP-OFFICE',      'description' => 'Expense'],
                    ['leg' => 'credit', 'account_source' => 'runtime', 'runtime_slot' => 'payment_account', 'description' => 'Cash/Bank paid'],
                ],
            ],
            'purchase.invoice' => [
                'module'           => 'purchase',
                'name'             => 'Purchase — Invoice Approval',
                'description'      => 'On approving a purchase invoice the payable is booked. Dr Inventory (asset), Cr Accounts Payable (liability) for the invoice total.',
                'transaction_type' => TransactionType::PURCHASE->value,
                'runtime_slots'    => [],
                'default_rules'    => [
                    ['leg' => 'debit',  'account_source' => 'fixed', 'account_code' => 'ASSET-INV', 'description' => 'Inventory / purchase'],
                    ['leg' => 'credit', 'account_source' => 'fixed', 'account_code' => 'LIAB-AP',   'description' => 'Accounts payable'],
                ],
            ],
            'purchase.supplier_advance' => [
                'module'           => 'purchase',
                'name'             => 'Purchase — Supplier Advance',
                'description'      => 'Advance paid against a purchase order (directly to the supplier or through an employee). Dr Supplier Advance (asset), Cr the payment account.',
                'transaction_type' => TransactionType::ADVANCE->value,
                'runtime_slots'    => ['payment_account' => 'Payment Account (Cash/Bank/MFS)'],
                'default_rules'    => [
                    ['leg' => 'debit',  'account_source' => 'fixed',   'account_code' => 'ASSET-SUP-ADV',   'description' => 'Supplier advance'],
                    ['leg' => 'credit', 'account_source' => 'runtime', 'runtime_slot' => 'payment_account', 'description' => 'Cash/Bank paid'],
                ],
            ],
            'purchase.supplier_payment' => [
                'module'           => 'purchase',
                'name'             => 'Purchase — Supplier Payment',
                'description'      => 'Payment against a supplier invoice. Dr Accounts Payable (liability settled), Cr Payment Account (cash/bank/mfs money leaves).',
                'transaction_type' => TransactionType::SUPPLIER_PAYMENT->value,
                'runtime_slots'    => ['payment_account' => 'Payment Account (Cash/Bank/MFS)'],
                'default_rules'    => [
                    ['leg' => 'debit',  'account_source' => 'fixed',   'account_code' => 'LIAB-AP',         'description' => 'Accounts payable'],
                    ['leg' => 'credit', 'account_source' => 'runtime', 'runtime_slot' => 'payment_account', 'description' => 'Cash/Bank paid'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    public static function exists(string $key): bool
    {
        return array_key_exists($key, self::all());
    }

    /**
     * Runtime slots an event exposes (slot key => label).
     *
     * @return array<string, string>
     */
    public static function slots(string $key): array
    {
        return self::find($key)['runtime_slots'] ?? [];
    }

    public static function hasSlot(string $key, string $slot): bool
    {
        return array_key_exists($slot, self::slots($key));
    }
}
