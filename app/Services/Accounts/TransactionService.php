<?php

namespace App\Services\Accounts;

use App\Enums\Accounts\AccountGroupType;
use App\Enums\Accounts\TransactionRelationType;
use App\Enums\Accounts\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class TransactionService
{
    public function __construct(private readonly LedgerService $ledger) {}

    // -------------------------------------------------------------------------
    // Post — primary entry point
    // -------------------------------------------------------------------------

    /**
     * Post a balanced double-entry transaction with full validation.
     *
     * Enforces:
     *   - At least two lines
     *   - All accounts must be LEDGER type (no GROUP posting)
     *   - Each line: debit XOR credit, no zero-only lines
     *   - Total debit === total credit (balanced)
     *
     * @param  array{datetime: string, type: string, method?: string|null, reference_no?: string|null,
     *               reference_type?: string|null, reference_id?: int|null, notes?: string|null,
     *               name?: string|null, phone?: string|null, attachments?: array|null,
     *               created_by?: int|null}  $header
     * @param  array<int, array{account_id: int, debit: float, credit: float, description?: string|null}>  $lines
     */
    public function post(
        array $header,
        array $lines,
        ?int $relatedTransactionId = null,
        ?TransactionRelationType $relationType = null,
    ): Transaction {
        $this->validateLines($lines);

        if ($relatedTransactionId !== null) {
            $header['related_transaction_id'] = $relatedTransactionId;
            $header['relation_type']           = $relationType?->value;
        }

        $header['created_by'] ??= (int) Auth::id();

        return $this->ledger->post($header, $lines);
    }

    // -------------------------------------------------------------------------
    // Reverse
    // -------------------------------------------------------------------------

    /**
     * Create a mirror-image reversal: all debit↔credit swapped.
     * Throws if the transaction has already been reversed.
     */
    public function reverse(
        Transaction $original,
        ?int $actorId = null,
        ?string $notes = null,
    ): Transaction {
        if (! $this->canBeReversed($original)) {
            throw new \DomainException('This transaction has already been reversed and cannot be reversed again.');
        }

        $original->loadMissing('lines');

        if ($original->lines->isEmpty()) {
            throw new \DomainException('Cannot reverse a transaction that has no ledger lines.');
        }

        $reversedLines = $original->lines->map(fn ($line) => [
            'account_id'  => (int) $line->account_id,
            'debit'       => (float) $line->credit,
            'credit'      => (float) $line->debit,
            'description' => $line->description ? 'Reversal: ' . $line->description : null,
        ])->all();

        $actorId = $actorId ?? (int) Auth::id();

        return $this->ledger->post(
            [
                'datetime'               => now()->format('Y-m-d H:i:s'),
                'type'                   => TransactionType::REVERSE->value,
                // method is NOT NULL (defaults to cash); mirror the original or fall back.
                'method'                 => $original->method ?: 'cash',
                // Carry the original's source reference so the reversal stays linked
                // to the same module record (e.g. payment schedule) — the ledger then
                // nets to zero for that reference.
                'reference_type'         => $original->reference_type,
                'reference_id'           => $original->reference_id,
                'reference_no'           => 'REV-' . $original->id,
                'notes'                  => $notes ?? ('Reversal of TXN-' . $original->id),
                'related_transaction_id' => $original->id,
                'relation_type'          => TransactionRelationType::REVERSE->value,
                'created_by'             => $actorId,
            ],
            $reversedLines,
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * A transaction can only be reversed once (no double-reversal).
     */
    public function canBeReversed(Transaction $tx): bool
    {
        return ! Transaction::query()
            ->where('related_transaction_id', $tx->id)
            ->where('relation_type', TransactionRelationType::REVERSE->value)
            ->exists();
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    /**
     * @param  array<int, array{account_id: int, debit: float, credit: float}>  $lines
     */
    private function validateLines(array $lines): void
    {
        if (count($lines) < 2) {
            throw new \DomainException('A transaction must have at least two lines (double-entry requires debit and credit sides).');
        }

        $accountIds = array_unique(array_map('intval', array_column($lines, 'account_id')));

        $accounts = Account::query()
            ->whereIn('id', $accountIds)
            ->get(['id', 'name', 'type'])
            ->keyBy('id');

        foreach ($lines as $index => $line) {
            $accountId = (int) ($line['account_id'] ?? 0);
            $debit     = (float) ($line['debit']  ?? 0);
            $credit    = (float) ($line['credit'] ?? 0);

            $account = $accounts->get($accountId);

            if (! $account) {
                throw new \DomainException("Line " . ($index + 1) . ": account ID {$accountId} does not exist.");
            }

            if ($account->type !== AccountGroupType::LEDGER) {
                throw new \DomainException(
                    "Line " . ($index + 1) . ": \"{$account->name}\" is a group account. " .
                    "Direct postings are only allowed to ledger accounts."
                );
            }

            if ($debit < 0 || $credit < 0) {
                throw new \DomainException("Line " . ($index + 1) . ": amounts must not be negative.");
            }

            if ($debit > 0 && $credit > 0) {
                throw new \DomainException(
                    "Line " . ($index + 1) . ": a single line cannot have both debit and credit. " .
                    "Split into separate lines."
                );
            }

            if ($debit === 0.0 && $credit === 0.0) {
                throw new \DomainException("Line " . ($index + 1) . ": must have a non-zero debit or credit amount.");
            }
        }

        // Balance check is already enforced inside LedgerService::post(),
        // but we do it here too so the error message is friendlier.
        $totalDebit  = round(array_sum(array_column($lines, 'debit')),  6);
        $totalCredit = round(array_sum(array_column($lines, 'credit')), 6);

        if ($totalDebit !== $totalCredit) {
            throw new \DomainException(sprintf(
                'Transaction is not balanced: total debit %s ≠ total credit %s.',
                number_format($totalDebit, 3),
                number_format($totalCredit, 3),
            ));
        }
    }
}
