<?php

namespace App\Services\Accounts;

use App\Enums\Accounts\TransactionRelationType;
use App\Enums\Accounts\TransactionType;
use App\Models\BankAccount;
use App\Models\BankingPaymentRequest;
use App\Models\Expense;
use App\Models\Transaction;
use App\Services\NumberSequenceService;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function __construct(private readonly NumberSequenceService $sequences) {}

    /**
     * Create a draft expense.  No banking request is created yet.
     *
     * @param array{
     *   title:string,
     *   date:string,
     *   expense_account_id:int,
     *   transaction_category_id:int,
     *   bank_account_id:int,
     *   amount:float|string,
     *   notes?:string|null
     * } $payload
     */
    public function create(array $payload, int $userId): Expense
    {
        return DB::transaction(function () use ($payload, $userId): Expense {
            $expenseNo = $this->sequences->next('EXP');

            return Expense::query()->create([
                'expense_no'              => $expenseNo,
                'title'                   => $payload['title'],
                'date'                    => $payload['date'],
                'amount'                  => round(max(0, (float) $payload['amount']), 3),
                'status'                  => 'draft',
                'expense_account_id'      => (int) $payload['expense_account_id'],
                'payment_account_id'      => null,
                'bank_account_id'         => (int) $payload['bank_account_id'],
                'transaction_category_id' => (int) $payload['transaction_category_id'],
                'transaction_id'          => null,
                'notes'                   => $payload['notes'] ?: null,
                'created_by'              => $userId,
            ]);
        });
    }

    /**
     * Post a draft expense — transitions status to 'pending' and creates the BankingPaymentRequest.
     */
    public function post(Expense $expense, int $userId): Expense
    {
        return DB::transaction(function () use ($expense, $userId): Expense {
            $locked = Expense::query()
                ->lockForUpdate()
                ->where('id', $expense->id)
                ->where('status', 'draft')
                ->firstOrFail();

            BankingPaymentRequest::query()->create([
                'request_no'              => BankingPaymentRequest::generateRequestNo(),
                'source_type'             => TransactionType::EXPENSE->value,
                'sourceable_type'         => Expense::class,
                'sourceable_id'           => $locked->id,
                'transaction_category_id' => $locked->transaction_category_id,
                'bank_account_id'         => $locked->bank_account_id,
                'amount'                  => $locked->amount,
                'description'             => 'Expense – ' . $locked->expense_no . ': ' . $locked->title,
                'status'                  => 'pending',
                'notes'                   => $locked->notes,
                'requested_by'            => $userId,
            ]);

            $locked->update(['status' => 'pending']);

            return $locked->fresh();
        });
    }

    /**
     * Complete an expense banking request — creates paired transactions and marks as posted.
     * Called by BankingManagement::markCompleted() when source_type='expense'.
     */
    public function completeExpense(BankingPaymentRequest $bankingRequest, int $userId): Expense
    {
        return DB::transaction(function () use ($bankingRequest, $userId): Expense {
            if ($bankingRequest->status !== 'released') {
                throw new \DomainException('Only a released payment request can be completed.');
            }

            if ($bankingRequest->sourceable_type !== Expense::class || ! $bankingRequest->sourceable_id) {
                throw new \DomainException('This payment request is not linked to an expense.');
            }

            $expense = Expense::query()
                ->lockForUpdate()
                ->where('id', $bankingRequest->sourceable_id)
                ->where('status', 'pending')
                ->firstOrFail();

            $bankAccount   = BankAccount::query()->findOrFail($bankingRequest->bank_account_id);
            $cashAccountId = (int) $bankAccount->account_id;
            $expAccountId  = (int) $expense->expense_account_id;
            $amount        = (float) $expense->amount;
            $datetime      = $expense->date->format('Y-m-d') . ' 00:00:00';
            $categoryId    = $bankingRequest->transaction_category_id ?? $expense->transaction_category_id;
            $notes         = $expense->notes ?? $expense->expense_no;

            // ------------------------------------------------------------------
            // TXN-CASH: CR bank/cash ledger — money physically leaves the account
            // ------------------------------------------------------------------
            $txnCash = Transaction::query()->create([
                'account_id'              => $cashAccountId,
                'datetime'                => $datetime,
                'type'                    => TransactionType::EXPENSE->value,
                'transaction_category_id' => $categoryId,
                'reference_type'          => 'expense',
                'reference_id'            => (int) $expense->id,
                'debit'                   => 0,
                'credit'                  => $amount,
                'name'                    => $expense->title,
                'notes'                   => $notes,
                'created_by'              => $userId,
            ]);

            // ------------------------------------------------------------------
            // TXN-EXPENSE: DR expense ledger account — cost is recorded
            // This is the transaction tracked in Expense.transaction_id
            // ------------------------------------------------------------------
            $txnExpense = Transaction::query()->create([
                'account_id'              => $expAccountId,
                'datetime'                => $datetime,
                'type'                    => TransactionType::EXPENSE->value,
                'transaction_category_id' => $categoryId,
                'reference_type'          => 'expense',
                'reference_id'            => (int) $expense->id,
                'debit'                   => $amount,
                'credit'                  => 0,
                'name'                    => $expense->title,
                'notes'                   => $notes,
                'related_transaction_id'  => $txnCash->id,
                'relation_type'           => TransactionRelationType::PAIR->value,
                'created_by'              => $userId,
            ]);

            // Update Expense → posted (tracks the DR side)
            $expense->update([
                'transaction_id'    => $txnExpense->id,
                'payment_account_id'=> $cashAccountId,
                'status'            => 'posted',
            ]);

            // Update BankingPaymentRequest → completed (tracks the CR/cash side)
            $bankingRequest->update([
                'transaction_id' => $txnCash->id,
                'status'         => 'completed',
                'completed_by'   => $userId,
                'completed_at'   => now(),
            ]);

            return $expense->fresh();
        });
    }
}
