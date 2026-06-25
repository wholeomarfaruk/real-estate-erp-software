<?php

namespace App\Enums\Accounts;

enum TransactionType: string
{
    case INCOME              = 'income';
    case EXPENSE             = 'expense';
    case ADVANCE_RECEIPT     = 'advance_receipt';
    case ADVANCE_PAYMENT     = 'advance_payment';
    case TRANSFER            = 'transfer';
    case ADJUSTMENT          = 'adjustment';
    case OPENING_BALANCE     = 'opening_balance';
    case PURCHASE            = 'purchase';
    case SUPPLIER_PAYMENT    = 'supplier_payment';
    case CUSTOMER_RECEIPT    = 'customer_receipt';
    case REVERSE             = 'reverse';
    case OWNER_INVESTMENT    = 'owner_investment';
    case OWNER_WITHDRAWAL    = 'owner_withdrawal';
    case LOAN_RECEIVED       = 'loan_received';
    case LOAN_REPAYMENT      = 'loan_repayment';
    case SALARY_PAYMENT        = 'salary_payment';
    case MATERIAL_CONSUMPTION  = 'material_consumption';
    case LABOR_BILL_PAYMENT    = 'labor_bill_payment';
    case EQUIPMENT_RENT_PAYMENT = 'equipment_rent_payment';
    case TRANSPORTATION_PAYMENT = 'transportation_payment';
    case UTILITY_BILL_PAYMENT  = 'utility_bill_payment';
    case ADVANCE               = 'advance';

    public function label(): string
    {
        return match ($this) {
            self::INCOME              => 'Income',
            self::EXPENSE             => 'Expense',
            self::ADVANCE_RECEIPT     => 'Advance Receipt',
            self::ADVANCE_PAYMENT     => 'Advance Payment',
            self::TRANSFER            => 'Transfer',
            self::ADJUSTMENT          => 'Adjustment',
            self::OPENING_BALANCE     => 'Opening Balance',
            self::PURCHASE            => 'Purchase',
            self::SUPPLIER_PAYMENT    => 'Supplier Payment',
            self::CUSTOMER_RECEIPT    => 'Customer Receipt',
            self::REVERSE             => 'Reverse',
            self::OWNER_INVESTMENT    => 'Owner Investment',
            self::OWNER_WITHDRAWAL    => 'Owner Withdrawal',
            self::LOAN_RECEIVED       => 'Loan Received',
            self::LOAN_REPAYMENT      => 'Loan Repayment',
            self::SALARY_PAYMENT        => 'Salary Payment',
            self::MATERIAL_CONSUMPTION  => 'Material Consumption',
            self::LABOR_BILL_PAYMENT    => 'Labor Bill Payment',
            self::EQUIPMENT_RENT_PAYMENT => 'Equipment Rent Payment',
            self::TRANSPORTATION_PAYMENT => 'Transportation Payment',
            self::UTILITY_BILL_PAYMENT  => 'Utility Bill Payment',
            self::ADVANCE               => 'Advance (Legacy)',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::INCOME              => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::EXPENSE             => 'bg-rose-50 text-rose-700 border-rose-200',
            self::ADVANCE_RECEIPT     => 'bg-violet-50 text-violet-700 border-violet-200',
            self::ADVANCE_PAYMENT     => 'bg-violet-100 text-violet-800 border-violet-300',
            self::TRANSFER            => 'bg-blue-50 text-blue-700 border-blue-200',
            self::ADJUSTMENT          => 'bg-amber-50 text-amber-700 border-amber-200',
            self::OPENING_BALANCE     => 'bg-gray-100 text-gray-600 border-gray-200',
            self::PURCHASE            => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            self::SUPPLIER_PAYMENT    => 'bg-yellow-50 text-yellow-700 border-yellow-200',
            self::CUSTOMER_RECEIPT    => 'bg-teal-50 text-teal-700 border-teal-200',
            self::REVERSE             => 'bg-orange-50 text-orange-700 border-orange-200',
            self::OWNER_INVESTMENT    => 'bg-green-50 text-green-700 border-green-200',
            self::OWNER_WITHDRAWAL    => 'bg-red-50 text-red-700 border-red-200',
            self::LOAN_RECEIVED       => 'bg-cyan-50 text-cyan-700 border-cyan-200',
            self::LOAN_REPAYMENT      => 'bg-cyan-100 text-cyan-800 border-cyan-300',
            self::SALARY_PAYMENT        => 'bg-pink-50 text-pink-700 border-pink-200',
            self::MATERIAL_CONSUMPTION  => 'bg-orange-50 text-orange-700 border-orange-200',
            self::LABOR_BILL_PAYMENT    => 'bg-purple-50 text-purple-700 border-purple-200',
            self::EQUIPMENT_RENT_PAYMENT => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            self::TRANSPORTATION_PAYMENT => 'bg-blue-50 text-blue-700 border-blue-200',
            self::UTILITY_BILL_PAYMENT  => 'bg-green-50 text-green-700 border-green-200',
            self::ADVANCE               => 'bg-gray-100 text-gray-600 border-gray-200',
        };
    }

    public function reportGroup(): ReportGroup
    {
        return match ($this) {
            self::INCOME,
            self::CUSTOMER_RECEIPT,
            self::OWNER_INVESTMENT,
            self::LOAN_RECEIVED,
            self::ADVANCE_RECEIPT
            => ReportGroup::RECEIPT,

            self::EXPENSE,
            self::SUPPLIER_PAYMENT,
            self::PURCHASE,
            self::OWNER_WITHDRAWAL,
            self::LOAN_REPAYMENT,
            self::SALARY_PAYMENT,
            self::MATERIAL_CONSUMPTION,
            self::LABOR_BILL_PAYMENT,
            self::EQUIPMENT_RENT_PAYMENT,
            self::TRANSPORTATION_PAYMENT,
            self::UTILITY_BILL_PAYMENT,
            self::ADVANCE_PAYMENT
            => ReportGroup::PAYMENT,

            self::TRANSFER,
            self::ADJUSTMENT,
            self::REVERSE,
            self::OPENING_BALANCE,
            self::ADVANCE
            => ReportGroup::NEUTRAL,
        };
    }

    public static function receipts(): array
    {
        return collect(self::cases())
            ->filter(fn($type) => $type->reportGroup() === ReportGroup::RECEIPT)
            ->map(fn($type) => $type->value)
            ->toArray();
    }

    public static function payments(): array
    {
        return collect(self::cases())
            ->filter(fn($type) => $type->reportGroup() === ReportGroup::PAYMENT)
            ->map(fn($type) => $type->value)
            ->toArray();
    }

    public static function neutral(): array
    {
        return collect(self::cases())
            ->filter(fn($type) => $type->reportGroup() === ReportGroup::NEUTRAL)
            ->map(fn($type) => $type->value)
            ->toArray();
    }
}
