<?php

namespace App\Services\Accounts;

use App\Enums\Accounts\AccountType;
use App\Enums\Accounts\TransactionType;
use App\Models\Account;
use App\Models\BankAccount;
use App\Models\BankingPaymentRequest;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function __construct(private readonly LedgerService $ledger) {}

    /**
     * Complete an expense banking request — posts a balanced double-entry
     * transaction (DR expense ledger, CR bank/cash ledger).
     * Called by BankingManagement::markCompleted() when source_type='expense'.
     */
    public function completeExpense(BankingPaymentRequest $bankingRequest, int $userId): BankingPaymentRequest
    {
        return DB::transaction(function () use ($bankingRequest, $userId): BankingPaymentRequest {
            if ($bankingRequest->status !== 'released') {
                throw new \DomainException('Only a released payment request can be completed.');
            }

            // Load the bank account
            $bankAccount = BankAccount::query()->findOrFail($bankingRequest->bank_account_id);
            $cashAccountId = (int) $bankAccount->account_id;

            // Get an expense account from the accounts table (type: ledger, name: contains "expense")
            $expenseAccount = Account::query()
                ->where('type', AccountType::LEDGER->value)
                ->where('name', 'like', '%expense%')
                ->first();

            if (! $expenseAccount) {
                throw new \DomainException('No expense account configured. Please set up an expense account in the chart of accounts.');
            }

            $expAccountId = (int) $expenseAccount->id;
            $amount = (float) $bankingRequest->amount;
            $datetime = now()->format('Y-m-d H:i:s');
            $categoryId = $bankingRequest->transaction_category_id;
            $notes = $bankingRequest->notes ?? $bankingRequest->description;

            $attachmentPaths = $bankingRequest->external_data['attachments'] ?? null;

            // Balanced double-entry: DR expense ledger (cost recorded),
            // CR bank/cash ledger (money physically leaves the account).
            $transaction = $this->ledger->post(
                [
                    'datetime'                => $datetime,
                    'type'                    => TransactionType::EXPENSE->value,
                    'transaction_category_id' => $categoryId,
                    'reference_type'          => $bankingRequest->sourceable_type,
                    'reference_id'            => $bankingRequest->sourceable_id,
                    'name'                    => 'Expense Payment',
                    'notes'                   => $notes,
                    'created_by'              => $userId,
                    'external_data'           => $bankingRequest->external_data,
                    'attachments'             => $attachmentPaths,
                ],
                [
                    ['account_id' => $expAccountId,  'debit' => $amount, 'credit' => 0,       'notes' => 'Expense'],
                    ['account_id' => $cashAccountId, 'debit' => 0,       'credit' => $amount, 'notes' => 'Cash'],
                ],
            );

            // Update BankingPaymentRequest → completed
            $bankingRequest->update([
                'transaction_id' => $transaction->id,
                'status'         => 'completed',
                'completed_by'   => $userId,
                'completed_at'   => now(),
            ]);

            return $bankingRequest->fresh();
        });
    }
}
