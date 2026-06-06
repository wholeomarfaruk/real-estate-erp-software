<?php

namespace App\Services\Accounts;

use App\Enums\Accounts\AccountType;
use App\Enums\Accounts\TransactionRelationType;
use App\Enums\Accounts\TransactionType;
use App\Models\Account;
use App\Models\BankAccount;
use App\Models\BankingPaymentRequest;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    /**
     * Complete an expense banking request — creates paired transactions.
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

            // ------------------------------------------------------------------
            // TXN-CASH: CR bank/cash ledger — money physically leaves the account
            // ------------------------------------------------------------------
            $txnCash = Transaction::query()->create([
                'account_id'              => $cashAccountId,
                'datetime'                => $datetime,
                'type'                    => TransactionType::EXPENSE->value,
                'transaction_category_id' => $categoryId,
                'reference_type'          => $bankingRequest->sourceable_type,
                'reference_id'            => $bankingRequest->sourceable_id,
                'debit'                   => 0,
                'credit'                  => $amount,
                'name'                    => 'Expense Payment',
                'notes'                   => $notes,
                'created_by'              => $userId,
            ]);

            // ------------------------------------------------------------------
            // TXN-EXPENSE: DR expense ledger account — cost is recorded
            // ------------------------------------------------------------------
            // $txnExpense = Transaction::query()->create([
            //     'account_id'              => $expAccountId,
            //     'datetime'                => $datetime,
            //     'type'                    => TransactionType::EXPENSE->value,
            //     'transaction_category_id' => $categoryId,
            //     'reference_type'          => $bankingRequest->sourceable_type,
            //     'reference_id'            => $bankingRequest->sourceable_id,
            //     'debit'                   => $amount,
            //     'credit'                  => 0,
            //     'name'                    => 'Expense',
            //     'notes'                   => $notes,
            //     'related_transaction_id'  => $txnCash->id,
            //     'relation_type'           => TransactionRelationType::PAIR->value,
            //     'created_by'              => $userId,
            // ]);

            // Update BankingPaymentRequest → completed
            $bankingRequest->update([
                'transaction_id' => $txnCash->id,
                'status'         => 'completed',
                'completed_by'   => $userId,
                'completed_at'   => now(),
            ]);

            return $bankingRequest->fresh();
        });
    }
}
