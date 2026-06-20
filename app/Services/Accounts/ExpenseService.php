<?php

namespace App\Services\Accounts;

use App\Accounting\PostingContext;
use App\Models\BankAccount;
use App\Models\BankingPaymentRequest;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function __construct(private readonly PostingEngine $engine) {}

    /**
     * Complete an expense banking request — auto-posts the balanced double-entry
     * via the `expense.payment` accounting event (Dr expense account [from
     * settings], Cr the bank/cash account the money leaves from).
     * Called by BankingManagement::markCompleted() when source_type='expense'.
     */
    public function completeExpense(BankingPaymentRequest $bankingRequest, int $userId): BankingPaymentRequest
    {
        return DB::transaction(function () use ($bankingRequest, $userId): BankingPaymentRequest {
            if ($bankingRequest->status !== 'released') {
                throw new \DomainException('Only a released payment request can be completed.');
            }

            $bankAccount = BankAccount::query()->findOrFail($bankingRequest->bank_account_id);

            $transaction = $this->engine->record(
                'expense.payment',
                new PostingContext(
                    amount: (float) $bankingRequest->amount,
                    datetime: now()->format('Y-m-d H:i:s'),
                    // The user's bank/cash is the runtime (credit) leg.
                    paymentAccountId: (int) $bankAccount->account_id,
                    referenceType: $bankingRequest->sourceable_type,
                    referenceId: $bankingRequest->sourceable_id,
                    name: 'Expense Payment',
                    notes: $bankingRequest->notes ?? $bankingRequest->description,
                    actorId: $userId,
                ),
            );

            // Carry attachments / external data the engine header doesn't model.
            $transaction->update([
                'external_data' => $bankingRequest->external_data,
                'attachments'   => $bankingRequest->external_data['attachments'] ?? null,
            ]);

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
