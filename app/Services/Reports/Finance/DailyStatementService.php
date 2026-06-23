<?php

namespace App\Services\Reports\Finance;

use App\Enums\Accounts\AccountType;
use App\Enums\Accounts\TransactionType;
use App\Models\Account;
use App\Models\TransactionLine;
use Carbon\Carbon;

class DailyStatementService
{
    public function build(array $filters): array
    {
        $date = Carbon::parse($filters['date'] ?? now()->toDateString())->startOfDay();

        $banks = $this->getBanks($date);
        $bankTotals = $this->getBankTotals($banks);
        $receipts = $this->getReceipts($date);
        $receiptTotals = $this->getReceiptTotals($receipts);
        $payments = $this->getPayments($date);
        $paymentTotals = $this->getPaymentTotals($payments);
        $cashAccount = $this->getCashAccount($date);
        $closing = [
            'cash' => $cashAccount['closing'],
            'bank' => $bankTotals['closing'],
        ];

        return [
            'title' => 'Daily Statement — ' . $date->format('d M Y'),
            'meta' => [
                'company_name' => config('app.name'),
                'address' => config('company.address'),
                'contact' => config('company.phone') . ' · ' . config('company.email'),
                'statement_date' => $date->format('d M Y'),
                'generated_at' => now()->format('d M Y, H:i'),
                'generated_by' => auth()->user()?->name ?? 'System',
            ],
            'banks' => $banks,
            'bank_totals' => $bankTotals,
            'receipts' => $receipts,
            'receipt_totals' => $receiptTotals,
            'payments' => $payments,
            'payment_totals' => $paymentTotals,
            'closing' => $closing,
        ];
    }

    /**
     * Get all liquid accounts with movements
     */
    private function getBanks(Carbon $date): array
    {
        $liquidAccounts = Account::query()
            ->whereIn('type', [
                AccountType::CASH->value,
                AccountType::BANK->value,
                AccountType::MFS->value,
                AccountType::WALLET->value,
            ])
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return $liquidAccounts->map(function (Account $account) use ($date) {
            $opening = $this->calculateOpeningBalance($account->id, $date);
            $closing = $this->calculateClosingBalance($account->id, $date);

            // Get daily movements by entry method
            $dailyLines = TransactionLine::query()
                ->where('account_id', $account->id)
                ->whereHas('transaction', fn($q) =>
                    $q->whereDate('datetime', '=', $date->toDateString())
                )
                ->with('transaction')
                ->get();

            // Classify movements by entry method
            $cashDeposit = 0;
            $onlineDeposit = 0;
            $cashWithdraw = 0;
            $onlineTransfer = 0;

            foreach ($dailyLines as $line) {
                $method = $line->transaction->method;

                if (!$method) {
                    continue;
                }

                $accountType = $method->accountType();

                // Deposits (money in)
                if ((float) $line->debit > 0) {
                    if ($accountType->value === 'cash') {
                        $cashDeposit += (int) $line->debit;
                    } else {
                        $onlineDeposit += (int) $line->debit;
                    }
                }

                // Withdrawals (money out)
                if ((float) $line->credit > 0) {
                    if ($accountType->value === 'cash') {
                        $cashWithdraw += (int) $line->credit;
                    } else {
                        $onlineTransfer += (int) $line->credit;
                    }
                }
            }

            return [
                'name' => $account->name,
                'account' => substr($account->code ?? '', -10),
                'opening' => $opening,
                'cash_deposit' => $cashDeposit,
                'online_deposit' => $onlineDeposit,
                'cash_withdraw' => $cashWithdraw,
                'online_transfer' => $onlineTransfer,
                'closing' => $closing,
            ];
        })->toArray();
    }

    /**
     * Get bank totals
     */
    private function getBankTotals(array $banks): array
    {
        return [
            'opening' => (int) collect($banks)->sum('opening'),
            'cash_deposit' => (int) collect($banks)->sum('cash_deposit'),
            'online_deposit' => (int) collect($banks)->sum('online_deposit'),
            'cash_withdraw' => (int) collect($banks)->sum('cash_withdraw'),
            'online_transfer' => (int) collect($banks)->sum('online_transfer'),
            'closing' => (int) collect($banks)->sum('closing'),
        ];
    }

    /**
     * Get daily receipts
     */
    private function getReceipts(Carbon $date): array
    {
        $liquidAccounts = Account::query()
            ->whereIn('type', [
                AccountType::CASH->value,
                AccountType::BANK->value,
                AccountType::MFS->value,
                AccountType::WALLET->value,
            ])
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        $receiptTypes = TransactionType::receipts();
        $neutralTypes = TransactionType::neutral();

        $receipts = TransactionLine::query()
            ->where('debit', '>', 0)
            ->whereIn('account_id', $liquidAccounts)
            ->whereHas('transaction', function ($query) use ($date, $receiptTypes, $neutralTypes) {
                $query->whereDate('datetime', '=', $date->toDateString())
                      ->where(function ($q) use ($receiptTypes, $neutralTypes) {
                          $q->whereIn('type', $receiptTypes)
                            ->orWhereIn('type', $neutralTypes);
                      })
                      ->where('type', '!=', TransactionType::OPENING_BALANCE->value);
            })
            ->with([
                'account',
                'transaction',
            ])
            ->orderBy('id')
            ->get();

        return $receipts->map(function (TransactionLine $line) use ($liquidAccounts) {
            // Exclude pure transfers (both accounts are liquid)
            $otherLine = $line->transaction->lines->firstWhere('account_id', '!=', $line->account_id);
            if ($otherLine && in_array($otherLine->account_id, $liquidAccounts)) {
                return null; // Skip transfer between liquid accounts
            }

            $sourceAccount = $otherLine?->account;
            $sourceName = $sourceAccount?->name ?? $line->transaction->name ?? 'Transfer';

            // Build account column with property details if available
            $accountDisplay = $sourceName;
            $enhancedParticulars = $line->transaction->notes ?? '';

            // Check if transaction references a PaymentSchedule (property unit payment)
            if ($line->transaction->reference_type === 'App\\Models\\PaymentSchedule') {
                $paymentSchedule = $line->transaction->reference ?? $line->transaction->reference()->with(['propertySale.propertyUnit.property', 'propertySale.customer'])->first();
                if ($paymentSchedule && $paymentSchedule->propertySale) {
                    $propertyUnit = $paymentSchedule->propertySale->propertyUnit;
                    $property = $propertyUnit?->property;
                    $customer = $paymentSchedule->propertySale->customer;
                    $scheduleLabel = $paymentSchedule->label();

                    // Build account: Customer Name, Property Name, Floor, Unit Code
                    $details = [];

                    if ($customer?->name) {
                        $details[] = $customer->name;
                    }

                    if ($property?->name) {
                        $details[] = $property->name;
                    }

                    if ($propertyUnit && $propertyUnit->floor) {
                        $floorLabel = is_object($propertyUnit->floor) ? $propertyUnit->floor->label : $propertyUnit->floor;
                        $details[] = $floorLabel;
                    }

                    if ($propertyUnit?->code) {
                        $details[] = $propertyUnit->code;
                    }

                    if (count($details) > 0) {
                        $accountDisplay = implode(", ", $details);
                    }

                    // Use schedule label as particulars
                    $enhancedParticulars = $scheduleLabel;
                    if ($line->transaction->notes) {
                        $enhancedParticulars .= " - " . $line->transaction->notes;
                    }
                }
            }

            return [
                'account' => $accountDisplay,
                'particulars' => $enhancedParticulars,
                'mr_no' => $line->transaction->reference_no,
                'folio' => null,
                'cash' => $line->account->type->value === 'cash' ? (int) $line->debit : 0,
                'bank' => $line->account->type->value === 'bank' ? (int) $line->debit : 0,
            ];
        })
        ->filter()
        ->values()
        ->toArray();
    }

    /**
     * Get receipt totals
     */
    private function getReceiptTotals(array $receipts): array
    {
        return [
            'cash' => (int) collect($receipts)->sum('cash'),
            'bank' => (int) collect($receipts)->sum('bank'),
        ];
    }

    /**
     * Get daily payments
     */
    private function getPayments(Carbon $date): array
    {
        $liquidAccounts = Account::query()
            ->whereIn('type', [
                AccountType::CASH->value,
                AccountType::BANK->value,
                AccountType::MFS->value,
                AccountType::WALLET->value,
            ])
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        $paymentTypes = TransactionType::payments();
        $neutralTypes = TransactionType::neutral();

        $payments = TransactionLine::query()
            ->where('credit', '>', 0)
            ->whereIn('account_id', $liquidAccounts)
            ->whereHas('transaction', function ($query) use ($date, $paymentTypes, $neutralTypes) {
                $query->whereDate('datetime', '=', $date->toDateString())
                      ->where(function ($q) use ($paymentTypes, $neutralTypes) {
                          $q->whereIn('type', $paymentTypes)
                            ->orWhereIn('type', $neutralTypes);
                      })
                      ->where('type', '!=', TransactionType::OPENING_BALANCE->value);
            })
            ->with([
                'account',
                'transaction',
            ])
            ->orderBy('id')
            ->get();

        return $payments->map(function (TransactionLine $line) use ($liquidAccounts) {
            // Exclude pure transfers (both accounts are liquid)
            $otherLine = $line->transaction->lines->firstWhere('account_id', '!=', $line->account_id);
            if ($otherLine && in_array($otherLine->account_id, $liquidAccounts)) {
                return null; // Skip transfer between liquid accounts
            }

            $destinationAccount = $otherLine?->account;
            $destinationName = $destinationAccount?->name ?? '-';

            $accountDisplay = $destinationName;
            $enhancedParticulars = $line->transaction->notes ?? '';
            $projNo = null;

            // Check if transaction references a Project
            if ($line->transaction->reference_type === 'App\\Models\\Project') {
                $project = $line->transaction->reference ?? $line->transaction->reference()->first();
                $projNo = $project?->code;
            }

            // Check if transaction references a PaymentSchedule (property unit payment)
            if ($line->transaction->reference_type === 'App\\Models\\PaymentSchedule') {
                $paymentSchedule = $line->transaction->reference ?? $line->transaction->reference()->with(['propertySale.propertyUnit.property', 'propertySale.customer'])->first();
                if ($paymentSchedule && $paymentSchedule->propertySale) {
                    $propertyUnit = $paymentSchedule->propertySale->propertyUnit;
                    $property = $propertyUnit?->property;
                    $customer = $paymentSchedule->propertySale->customer;
                    $scheduleLabel = $paymentSchedule->label();

                    // Build account: Customer Name, Property Name, Floor, Unit Code
                    $details = [];

                    if ($customer?->name) {
                        $details[] = $customer->name;
                    }

                    if ($property?->name) {
                        $details[] = $property->name;
                    }

                    if ($propertyUnit && $propertyUnit->floor) {
                        $floorLabel = is_object($propertyUnit->floor) ? $propertyUnit->floor->label : $propertyUnit->floor;
                        $details[] = $floorLabel;
                    }

                    if ($propertyUnit?->code) {
                        $details[] = $propertyUnit->code;
                    }

                    if (count($details) > 0) {
                        $accountDisplay = implode(", ", $details);
                    }

                    // Use schedule label as particulars
                    $enhancedParticulars = $scheduleLabel;
                    if ($line->transaction->notes) {
                        $enhancedParticulars .= " - " . $line->transaction->notes;
                    }
                }
            }

            return [
                'account' => $accountDisplay,
                'particulars' => $enhancedParticulars,
                'proj_no' => $projNo,
                'folio' => null,
                'cash' => $line->account->type->value === 'cash' ? (int) $line->credit : 0,
            ];
        })
        ->filter()
        ->values()
        ->toArray();
    }

    /**
     * Get payment totals
     */
    private function getPaymentTotals(array $payments): array
    {
        return [
            'cash' => (int) collect($payments)->sum('cash'),
        ];
    }

    /**
     * Get cash account info
     */
    private function getCashAccount(Carbon $date): array
    {
        $cashAccount = Account::query()
            ->where('type', AccountType::CASH->value)
            ->where('is_active', true)
            ->first();

        if (!$cashAccount) {
            return ['opening' => 0, 'closing' => 0];
        }

        return [
            'opening' => (int) $this->calculateOpeningBalance($cashAccount->id, $date),
            'closing' => (int) $this->calculateClosingBalance($cashAccount->id, $date),
        ];
    }

    /**
     * Calculate opening balance
     */
    private function calculateOpeningBalance(int $accountId, Carbon $date): float
    {
        $balance = TransactionLine::query()
            ->where('account_id', $accountId)
            ->whereHas('transaction', fn ($q) =>
                $q->where('datetime', '<', $date->startOfDay())
            )
            ->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) as balance')
            ->value('balance') ?? 0;

        return (float) $balance;
    }

    /**
     * Calculate closing balance
     */
    private function calculateClosingBalance(int $accountId, Carbon $date): float
    {
        $balance = TransactionLine::query()
            ->where('account_id', $accountId)
            ->whereHas('transaction', fn ($q) =>
                $q->where('datetime', '<=', $date->endOfDay())
            )
            ->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) as balance')
            ->value('balance') ?? 0;

        return (float) $balance;
    }
}
