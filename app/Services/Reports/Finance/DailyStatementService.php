<?php

namespace App\Services\Reports\Finance;

use App\Enums\Accounts\AccountType;
use App\Enums\Accounts\TransactionType;
use App\Models\Account;
use App\Models\PaymentSchedule;
use App\Models\Project;
use App\Models\TransactionLine;
use Carbon\Carbon;

class DailyStatementService
{
    private const LIQUID_TYPES = [
        AccountType::CASH->value,
        AccountType::BANK->value,
        AccountType::MFS->value,
        AccountType::WALLET->value,
    ];

    public function build(array $filters): array
    {
        $date = Carbon::parse($filters['date'] ?? now()->toDateString())->startOfDay();

        $banks = $this->getBanks($date);
        $bankTotals = $this->getBankTotals($banks);
        $receipts = $this->getReceipts($date);
        $receiptTotals = $this->getReceiptTotals($receipts);
        $payments = $this->getPayments($date);
        $paymentTotals = $this->getPaymentTotals($payments);
        // Cash = all cash accounts; Bank = bank, MFS and wallet accounts.
        // (bank_totals.closing is every liquid account, cash included.)
        $split = fn (string $key) => [
            'cash' => round(collect($banks)->where('type', AccountType::CASH->value)->sum($key), 2),
            'bank' => round(collect($banks)->where('type', '!=', AccountType::CASH->value)->sum($key), 2),
        ];
        $opening = $split('opening');
        $closing = $split('closing');

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
            'opening' => $opening,
            'closing' => $closing,
        ];
    }

    /**
     * Get all liquid accounts with movements
     */
    private function getBanks(Carbon $date): array
    {
        $liquidAccounts = Account::query()
            ->whereIn('type', self::LIQUID_TYPES)
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

            $ownType = $account->type instanceof AccountType ? $account->type->value : (string) $account->type;

            foreach ($dailyLines as $line) {
                // No entry method → classify by the account itself, so the row still
                // reconciles (opening + deposits − withdrawals = closing).
                $accountType = $line->transaction->method?->accountType()
                    ?? ($ownType === AccountType::CASH->value ? AccountType::CASH : AccountType::BANK);

                // Deposits (money in)
                if ((float) $line->debit > 0) {
                    if ($accountType->value === 'cash') {
                        $cashDeposit += round((float) $line->debit, 2);
                    } else {
                        $onlineDeposit += round((float) $line->debit, 2);
                    }
                }

                // Withdrawals (money out)
                if ((float) $line->credit > 0) {
                    if ($accountType->value === 'cash') {
                        $cashWithdraw += round((float) $line->credit, 2);
                    } else {
                        $onlineTransfer += round((float) $line->credit, 2);
                    }
                }
            }

            return [
                'name' => $account->name,
                'type' => $ownType,
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
            'opening' => round(collect($banks)->sum('opening'), 2),
            'cash_deposit' => round(collect($banks)->sum('cash_deposit'), 2),
            'online_deposit' => round(collect($banks)->sum('online_deposit'), 2),
            'cash_withdraw' => round(collect($banks)->sum('cash_withdraw'), 2),
            'online_transfer' => round(collect($banks)->sum('online_transfer'), 2),
            'closing' => round(collect($banks)->sum('closing'), 2),
        ];
    }

    /**
     * Liquid accounts (cash / bank / MFS / wallet): id => type value.
     */
    private function liquidAccountTypes(): array
    {
        return $this->liquidTypes ??= Account::query()
            ->whereIn('type', self::LIQUID_TYPES)
            ->where('is_active', true)
            ->pluck('type', 'id')
            ->map(fn ($type) => $type instanceof AccountType ? $type->value : (string) $type)
            ->all();
    }

    private ?array $liquidTypes = null;

    /**
     * Cash accounts go to the Cash column; bank, MFS and wallet to the Bank column.
     */
    private function liquidColumn(?string $accountType): string
    {
        return $accountType === AccountType::CASH->value ? 'cash' : 'bank';
    }

    /**
     * Daily receipts — money into a liquid account.
     */
    private function getReceipts(Carbon $date): array
    {
        return $this->getMovements($date, 'debit', TransactionType::receipts());
    }

    /**
     * Daily payments — money out of a liquid account.
     */
    private function getPayments(Carbon $date): array
    {
        return $this->getMovements($date, 'credit', TransactionType::payments());
    }

    /**
     * Rows for the Receipts (debit side) or Payments (credit side) table.
     * Neutral types (adjustments…) are included; transfers between two liquid
     * accounts and opening balances are not.
     */
    private function getMovements(Carbon $date, string $side, array $types): array
    {
        $liquid = $this->liquidAccountTypes();
        $types = array_merge($types, TransactionType::neutral());

        $lines = TransactionLine::query()
            ->where($side, '>', 0)
            ->whereIn('account_id', array_keys($liquid))
            ->whereHas('transaction', fn ($q) => $q
                ->whereDate('datetime', '=', $date->toDateString())
                ->whereIn('type', $types)
                ->where('type', '!=', TransactionType::OPENING_BALANCE->value))
            // Not eager-loading `reference`: many rows store a plain string type
            // (transfer, purchase_invoice…) with no morph map, which would throw.
            ->with('transaction.lines.account')
            ->orderBy('id')
            ->get();

        // Project codes in one query.
        $projectCodes = Project::whereIn('id', $lines->pluck('transaction')
                ->where('reference_type', Project::class)->pluck('reference_id')->unique()->filter())
            ->pluck('code', 'id');

        return $lines->map(function (TransactionLine $line) use ($liquid, $side, $projectCodes) {
            $transaction = $line->transaction;
            $otherLines = $transaction->lines->where('account_id', '!=', $line->account_id);

            // Transfer between liquid accounts — not a receipt / payment.
            if ($otherLines->isNotEmpty() && $otherLines->every(fn ($l) => isset($liquid[$l->account_id]))) {
                return null;
            }

            // The counter account (first non-liquid one).
            $counter = $otherLines->first(fn ($l) => ! isset($liquid[$l->account_id]))?->account;
            $account = $counter?->name ?? $transaction->name ?? ($side === 'debit' ? 'Transfer' : '-');
            $particulars = (string) ($transaction->notes ?? '');

            $projectCode = $transaction->reference_type === Project::class
                ? $projectCodes->get($transaction->reference_id)
                : null;

            if ($transaction->reference_type === PaymentSchedule::class) {
                $schedule = PaymentSchedule::with(['propertySale.propertyUnit.property', 'propertySale.customer'])
                    ->find($transaction->reference_id);
                if ($schedule?->propertySale) {
                    [$account, $particulars] = $this->scheduleDetails($schedule, $transaction->notes, $account);
                }
            }

            $amount = round((float) $line->{$side}, 2);
            $column = $this->liquidColumn($liquid[$line->account_id] ?? null);

            return [
                'account'      => $account,
                'particulars'  => $particulars,
                'voucher_no'   => $transaction->voucher_no ?: null,
                'project_code' => $projectCode ?: null,
                'cash'         => $column === 'cash' ? $amount : 0,
                'bank'         => $column === 'bank' ? $amount : 0,
            ];
        })
        ->filter()
        ->values()
        ->toArray();
    }

    /**
     * Account / particulars for a property installment:
     * "Customer, Property, Floor, Unit" and the schedule label.
     */
    private function scheduleDetails(PaymentSchedule $schedule, ?string $notes, string $fallbackAccount): array
    {
        $unit = $schedule->propertySale->propertyUnit;
        $floor = $unit?->floor;

        $details = array_filter([
            $schedule->propertySale->customer?->name,
            $unit?->property?->name,
            is_object($floor) ? $floor->label : $floor,
            $unit?->code,
        ]);

        $particulars = $schedule->label() . ($notes ? ' - ' . $notes : '');

        return [$details ? implode(', ', $details) : $fallbackAccount, $particulars];
    }

    /**
     * Get receipt totals
     */
    private function getReceiptTotals(array $receipts): array
    {
        return [
            'cash' => round(collect($receipts)->sum('cash'), 2),
            'bank' => round(collect($receipts)->sum('bank'), 2),
        ];
    }

    /**
     * Get payment totals
     */
    private function getPaymentTotals(array $payments): array
    {
        return [
            'cash' => round(collect($payments)->sum('cash'), 2),
            'bank' => round(collect($payments)->sum('bank'), 2),
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
                $q->where('datetime', '<', $date->copy()->startOfDay())
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
                $q->where('datetime', '<=', $date->copy()->endOfDay())
            )
            ->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) as balance')
            ->value('balance') ?? 0;

        return (float) $balance;
    }
}
