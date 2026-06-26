<?php

namespace Database\Seeders;

use App\Models\AccountEntryCategory;
use App\Models\AccountEntryType;
use Illuminate\Database\Seeder;

class AccountEntryTypeSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCategories();
        $this->seedLockedEntries();
    }

    private function seedCategories(): void
    {
        $categories = [
            ['key' => 'receipts', 'title' => 'Receipts', 'description' => 'Record income and advances received', 'sort_order' => 10, 'is_locked' => true],
            ['key' => 'payments', 'title' => 'Payments', 'description' => 'Record expenses and withdrawals paid', 'sort_order' => 20, 'is_locked' => true],
            ['key' => 'transfers', 'title' => 'Transfers', 'description' => 'Transfer funds and adjust balances', 'sort_order' => 30, 'is_locked' => true],
            ['key' => 'opening', 'title' => 'Opening Balances', 'description' => 'Set initial account balances', 'sort_order' => 40, 'is_locked' => true],
        ];

        foreach ($categories as $cat) {
            AccountEntryCategory::updateOrCreate(
                ['key' => $cat['key']],
                array_merge($cat, ['is_active' => true])
            );
        }
    }

    private function seedLockedEntries(): void
    {
        $entries = [
            // Receipts
            ['slug' => 'income', 'category_key' => 'receipts', 'name' => 'Income', 'description' => 'Record general income received', 'form_component' => 'App\\Livewire\\Admin\\Accounts\\Entry\\Receipts\\IncomeForm', 'workflow' => 'banking_request', 'transaction_type' => 'income', 'debit_account_type' => 'cash,bank,mfs,wallet', 'credit_account_group' => 'income', 'permission' => 'accounts.entry.receipts.create', 'sort_order' => 10, 'is_locked' => true],
            ['slug' => 'customer-receipt', 'category_key' => 'receipts', 'name' => 'Customer Receipt', 'description' => 'Record customer payment for receivables', 'form_component' => 'App\\Livewire\\Admin\\Accounts\\Entry\\Receipts\\CustomerReceiptForm', 'workflow' => 'banking_request', 'transaction_type' => 'customer_receipt', 'debit_account_type' => 'cash,bank,mfs,wallet', 'credit_account_group' => 'asset', 'permission' => 'accounts.entry.receipts.create', 'sort_order' => 20, 'is_locked' => true],
            ['slug' => 'owner-investment', 'category_key' => 'receipts', 'name' => 'Owner Investment', 'description' => 'Record owner capital injection', 'form_component' => 'App\\Livewire\\Admin\\Accounts\\Entry\\Receipts\\OwnerInvestmentForm', 'workflow' => 'banking_request', 'transaction_type' => 'owner_investment', 'debit_account_type' => 'cash,bank,mfs,wallet', 'credit_account_group' => 'equity', 'permission' => 'accounts.entry.receipts.create', 'sort_order' => 30, 'is_locked' => true],
            ['slug' => 'loan-received', 'category_key' => 'receipts', 'name' => 'Loan Received', 'description' => 'Record loans and borrowings', 'form_component' => 'App\\Livewire\\Admin\\Accounts\\Entry\\Receipts\\LoanReceivedForm', 'workflow' => 'banking_request', 'transaction_type' => 'loan_received', 'debit_account_type' => 'cash,bank,mfs,wallet', 'credit_account_group' => 'liability', 'permission' => 'accounts.entry.receipts.create', 'sort_order' => 40, 'is_locked' => true],
            ['slug' => 'advance-receipt', 'category_key' => 'receipts', 'name' => 'Advance Receipt', 'description' => 'Record advances received from customers', 'form_component' => 'App\\Livewire\\Admin\\Accounts\\Entry\\Receipts\\AdvanceReceiptForm', 'workflow' => 'banking_request', 'transaction_type' => 'advance_receipt', 'debit_account_type' => 'cash,bank,mfs,wallet', 'credit_account_group' => 'liability', 'permission' => 'accounts.entry.receipts.create', 'sort_order' => 50, 'is_locked' => true],

            // Payments
            ['slug' => 'supplier-payment', 'category_key' => 'payments', 'name' => 'Supplier Payment', 'description' => 'Pay supplier invoices', 'form_component' => 'App\\Livewire\\Admin\\Accounts\\Entry\\Payments\\SupplierPaymentForm', 'workflow' => 'banking_request', 'transaction_type' => 'supplier_payment', 'debit_account_group' => 'liability', 'credit_account_type' => 'cash,bank', 'permission' => 'accounts.entry.payments.create', 'sort_order' => 10, 'is_locked' => true],
            ['slug' => 'owner-withdrawal', 'category_key' => 'payments', 'name' => 'Owner Withdrawal', 'description' => 'Record owner cash withdrawals', 'form_component' => 'App\\Livewire\\Admin\\Accounts\\Entry\\Payments\\OwnerWithdrawalForm', 'workflow' => 'banking_request', 'transaction_type' => 'owner_withdrawal', 'debit_account_group' => 'equity', 'credit_account_type' => 'cash,bank', 'permission' => 'accounts.entry.payments.create', 'sort_order' => 20, 'is_locked' => true],
            ['slug' => 'loan-repayment', 'category_key' => 'payments', 'name' => 'Loan Repayment', 'description' => 'Repay loans and borrowings', 'form_component' => 'App\\Livewire\\Admin\\Accounts\\Entry\\Payments\\LoanRepaymentForm', 'workflow' => 'banking_request', 'transaction_type' => 'loan_repayment', 'debit_account_group' => 'liability', 'credit_account_type' => 'cash,bank', 'permission' => 'accounts.entry.payments.create', 'sort_order' => 30, 'is_locked' => true],
            ['slug' => 'salary-payment', 'category_key' => 'payments', 'name' => 'Salary Payment', 'description' => 'Pay employee salaries', 'form_component' => 'App\\Livewire\\Admin\\Accounts\\Entry\\Payments\\SalaryPaymentForm', 'workflow' => 'banking_request', 'transaction_type' => 'salary_payment', 'debit_account_group' => 'expense', 'credit_account_type' => 'cash,bank', 'permission' => 'accounts.entry.payments.create', 'sort_order' => 40, 'is_locked' => true],
            ['slug' => 'labor-bill-payment', 'category_key' => 'payments', 'name' => 'Labor Bill Payment', 'description' => 'Pay labor and contractor bills', 'form_component' => 'App\\Livewire\\Admin\\Accounts\\Entry\\Payments\\LaborBillPaymentForm', 'workflow' => 'banking_request', 'transaction_type' => 'labor_bill', 'debit_account_group' => 'expense', 'credit_account_type' => 'cash,bank', 'permission' => 'accounts.entry.payments.create', 'sort_order' => 50, 'is_locked' => true],
            ['slug' => 'equipment-rent-payment', 'category_key' => 'payments', 'name' => 'Equipment Rent', 'description' => 'Pay equipment rental expenses', 'form_component' => 'App\\Livewire\\Admin\\Accounts\\Entry\\Payments\\EquipmentRentPaymentForm', 'workflow' => 'banking_request', 'transaction_type' => 'equipment_rent', 'debit_account_group' => 'expense', 'credit_account_type' => 'cash,bank', 'permission' => 'accounts.entry.payments.create', 'sort_order' => 60, 'is_locked' => true],
            ['slug' => 'transportation-payment', 'category_key' => 'payments', 'name' => 'Transportation', 'description' => 'Pay transportation and travel expenses', 'form_component' => 'App\\Livewire\\Admin\\Accounts\\Entry\\Payments\\TransportationPaymentForm', 'workflow' => 'banking_request', 'transaction_type' => 'transportation', 'debit_account_group' => 'expense', 'credit_account_type' => 'cash,bank', 'permission' => 'accounts.entry.payments.create', 'sort_order' => 70, 'is_locked' => true],
            ['slug' => 'utility-bill-payment', 'category_key' => 'payments', 'name' => 'Utility Bill', 'description' => 'Pay utility and service bills', 'form_component' => 'App\\Livewire\\Admin\\Accounts\\Entry\\Payments\\UtilityBillPaymentForm', 'workflow' => 'banking_request', 'transaction_type' => 'utility_bill', 'debit_account_group' => 'expense', 'credit_account_type' => 'cash,bank', 'permission' => 'accounts.entry.payments.create', 'sort_order' => 80, 'is_locked' => true],
            ['slug' => 'advance-payment', 'category_key' => 'payments', 'name' => 'Advance Payment', 'description' => 'Pay advances to suppliers/employees', 'form_component' => 'App\\Livewire\\Admin\\Accounts\\Entry\\Payments\\AdvancePaymentForm', 'workflow' => 'banking_request', 'transaction_type' => 'advance_payment', 'debit_account_group' => 'asset', 'credit_account_type' => 'cash,bank', 'permission' => 'accounts.entry.payments.create', 'sort_order' => 90, 'is_locked' => true],
            ['slug' => 'purchase', 'category_key' => 'payments', 'name' => 'Purchase', 'description' => 'Record purchase of materials and goods', 'form_component' => 'App\\Livewire\\Admin\\Accounts\\Entry\\Payments\\PurchaseForm', 'workflow' => 'banking_request', 'transaction_type' => 'purchase', 'debit_account_group' => 'asset', 'credit_account_type' => 'cash,bank', 'permission' => 'accounts.entry.payments.create', 'sort_order' => 100, 'is_locked' => true],

            // Transfers
            ['slug' => 'fund-transfer', 'category_key' => 'transfers', 'name' => 'Fund Transfer', 'description' => 'Transfer funds between accounts', 'form_component' => 'App\\Livewire\\Admin\\Accounts\\Entry\\Transfers\\FundTransferForm', 'workflow' => 'direct_ledger', 'transaction_type' => 'transfer', 'permission' => 'accounts.entry.transfers.create', 'sort_order' => 10, 'is_locked' => true],
            ['slug' => 'adjustment', 'category_key' => 'transfers', 'name' => 'Adjustment', 'description' => 'Adjust account balances', 'form_component' => 'App\\Livewire\\Admin\\Accounts\\Entry\\Transfers\\AdjustmentForm', 'workflow' => 'direct_ledger', 'transaction_type' => 'adjustment', 'permission' => 'accounts.entry.transfers.create', 'sort_order' => 20, 'is_locked' => true],
            ['slug' => 'reverse', 'category_key' => 'transfers', 'name' => 'Reverse Entry', 'description' => 'Reverse a previous entry', 'form_component' => 'App\\Livewire\\Admin\\Accounts\\Entry\\Transfers\\ReverseForm', 'workflow' => 'direct_ledger', 'transaction_type' => 'reverse', 'permission' => 'accounts.entry.transfers.create', 'sort_order' => 30, 'is_locked' => true],

            // Opening
            ['slug' => 'opening-balance', 'category_key' => 'opening', 'name' => 'Opening Balance', 'description' => 'Set initial account balances', 'form_component' => 'App\\Livewire\\Admin\\Accounts\\Entry\\Opening\\OpeningBalanceForm', 'workflow' => 'direct_ledger', 'transaction_type' => 'opening_balance', 'permission' => 'accounts.entry.opening.create', 'sort_order' => 10, 'is_locked' => true],
        ];

        foreach ($entries as $entry) {
            AccountEntryType::updateOrCreate(
                ['slug' => $entry['slug']],
                array_merge($entry, ['is_active' => true, 'is_visible' => true])
            );
        }
    }
}
