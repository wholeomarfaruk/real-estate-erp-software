<?php

namespace App\Services\Accounts;

use App\Models\Transaction;
use App\Models\TransactionLine;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    /**
     * Post a balanced double-entry transaction.
     *
     * @param  array{
     *   datetime: string,
     *   type: string,
     *   reference_type?: string|null,
     *   reference_id?: int|null,
     *   reference_no?: string|null,
     *   notes?: string|null,
     *   name?: string|null,
     *   phone?: string|null,
     *   method?: string|null,
     *   attachments?: array|null,
     *   related_transaction_id?: int|null,
     *   relation_type?: string|null,
     *   created_by?: int|null,
     * }  $header
     * @param  array<int, array{account_id: int, debit: float, credit: float, description?: string|null}>  $lines
     */
    public function post(array $header, array $lines): Transaction
    {
        $totalDebit  = round(array_sum(array_column($lines, 'debit')), 6);
        $totalCredit = round(array_sum(array_column($lines, 'credit')), 6);

        if ($totalDebit !== $totalCredit) {
            throw new \DomainException(sprintf(
                'Transaction is not balanced. Total debit %s ≠ total credit %s.',
                $totalDebit,
                $totalCredit
            ));
        }

        return DB::transaction(function () use ($header, $lines): Transaction {
            // The transaction header no longer stores account_id/debit/credit/
            // transaction_category_id — per-account movements live entirely in
            // transaction_lines. Only header metadata is persisted here.
            $allowedKeys = [
                'datetime', 'type', 'reference_no', 'reference_type',
                'reference_id', 'notes', 'method', 'name', 'phone', 'attachments', 'external_data',
                'related_transaction_id', 'relation_type', 'created_by', 'updated_by',
            ];

            $transaction = Transaction::query()->create(
                array_intersect_key($header, array_flip($allowedKeys))
            );

            foreach ($lines as $line) {
                TransactionLine::query()->create([
                    'transaction_id' => $transaction->id,
                    'account_id'     => (int) $line['account_id'],
                    'debit'          => (float) ($line['debit']  ?? 0),
                    'credit'         => (float) ($line['credit'] ?? 0),
                    'notes'          => $line['notes'] ?? $line['description'] ?? null,
                ]);
            }

            return $transaction;
        });
    }
}
