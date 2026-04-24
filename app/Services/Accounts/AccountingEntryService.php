<?php

namespace App\Services\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\AccountCollection;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\PurchasePayable;
use App\Models\Transaction;
use App\Models\TransactionAttachment;
use Illuminate\Support\Facades\DB;

class AccountingEntryService
{
    public const PAYABLE_REFERENCE_TYPE = 'purchase_payable';

    /**
     * @param  array<string, mixed>  $payload
     */
    public function savePayment(array $payload, ?Payment $payment = null, ?int $actorId = null): Payment
    {
        $resolvedActorId = $this->resolveActorId($actorId);

        return DB::transaction(function () use ($payload, $payment, $resolvedActorId): Payment {
            $attachmentIds = is_array($payload['attachment_ids'] ?? null) ? $payload['attachment_ids'] : [];
            unset($payload['attachment_ids']);

            $record = null;
            $transaction = null;
            $oldReferenceType = null;
            $oldReferenceId = null;

            if ($payment) {
                $record = Payment::query()->lockForUpdate()->findOrFail($payment->id);
                $transaction = Transaction::query()->lockForUpdate()->findOrFail($record->transaction_id);
                $oldReferenceType = $record->reference_type;
                $oldReferenceId = $record->reference_id;
            }

            if (! $transaction) {
                $transaction = Transaction::query()->create([
                    'date' => $payload['date'],
                    'type' => TransactionType::PAYMENT->value,
                    'reference_type' => null,
                    'reference_id' => null,
                    'notes' => $payload['notes'] ?? null,
                    'created_by' => $resolvedActorId,
                ]);
            }

            $record ??= new Payment();
            $record->fill($payload);
            $record->transaction_id = (int) $transaction->id;
            $record->payment_no = $record->payment_no ?: $this->generatePaymentNo();
            $record->created_by = $record->created_by ?: $resolvedActorId;
            $record->save();

            $transaction->update([
                'date' => $record->date,
                'type' => TransactionType::PAYMENT->value,
                'reference_type' => 'payment',
                'reference_id' => (int) $record->id,
                'notes' => $record->notes,
            ]);

            $this->syncTransactionLines($transaction, [
                [
                    'account_id' => (int) $record->purpose_account_id,
                    'debit' => (float) $record->amount,
                    'credit' => 0,
                    'description' => 'Payment debit entry',
                ],
                [
                    'account_id' => (int) $record->payment_account_id,
                    'debit' => 0,
                    'credit' => (float) $record->amount,
                    'description' => 'Payment credit entry',
                ],
            ]);

            $this->syncAttachments($transaction, $attachmentIds, $resolvedActorId);
            $this->syncPayableIfReferenced($record->reference_type, $record->reference_id);

            if ($oldReferenceType === self::PAYABLE_REFERENCE_TYPE && (int) $oldReferenceId > 0) {
                if ($oldReferenceType !== $record->reference_type || (int) $oldReferenceId !== (int) $record->reference_id) {
                    $this->recalculatePurchasePayable((int) $oldReferenceId);
                }
            }

            return $record->refresh();
        });
    }

    public function deletePayment(Payment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $record = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $referenceType = $record->reference_type;
            $referenceId = $record->reference_id;
            $transaction = Transaction::query()->lockForUpdate()->find($record->transaction_id);

            $record->delete();

            if ($transaction) {
                $transaction->lines()->delete();
                $transaction->delete();
            }

            if ($referenceType === self::PAYABLE_REFERENCE_TYPE && (int) $referenceId > 0) {
                $this->recalculatePurchasePayable((int) $referenceId);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function saveCollection(array $payload, ?AccountCollection $collection = null, ?int $actorId = null): AccountCollection
    {
        $resolvedActorId = $this->resolveActorId($actorId);

        return DB::transaction(function () use ($payload, $collection, $resolvedActorId): AccountCollection {
            $attachmentIds = is_array($payload['attachment_ids'] ?? null) ? $payload['attachment_ids'] : [];
            unset($payload['attachment_ids']);

            $record = null;
            $transaction = null;

            if ($collection) {
                $record = AccountCollection::query()->lockForUpdate()->findOrFail($collection->id);
                $transaction = Transaction::query()->lockForUpdate()->findOrFail($record->transaction_id);
            }

            if (! $transaction) {
                $transaction = Transaction::query()->create([
                    'date' => $payload['date'],
                    'type' => TransactionType::COLLECTION->value,
                    'reference_type' => null,
                    'reference_id' => null,
                    'notes' => $payload['notes'] ?? null,
                    'created_by' => $resolvedActorId,
                ]);
            }

            $record ??= new AccountCollection();
            $record->fill($payload);
            $record->transaction_id = (int) $transaction->id;
            $record->collection_no = $record->collection_no ?: $this->generateCollectionNo();
            $record->created_by = $record->created_by ?: $resolvedActorId;
            $record->save();

            $transaction->update([
                'date' => $record->date,
                'type' => TransactionType::COLLECTION->value,
                'reference_type' => 'collection',
                'reference_id' => (int) $record->id,
                'notes' => $record->notes,
            ]);

            $this->syncTransactionLines($transaction, [
                [
                    'account_id' => (int) $record->collection_account_id,
                    'debit' => (float) $record->amount,
                    'credit' => 0,
                    'description' => 'Collection debit entry',
                ],
                [
                    'account_id' => (int) $record->target_account_id,
                    'debit' => 0,
                    'credit' => (float) $record->amount,
                    'description' => 'Collection credit entry',
                ],
            ]);

            $this->syncAttachments($transaction, $attachmentIds, $resolvedActorId);

            return $record->refresh();
        });
    }

    public function deleteCollection(AccountCollection $collection): void
    {
        DB::transaction(function () use ($collection): void {
            $record = AccountCollection::query()->lockForUpdate()->findOrFail($collection->id);
            $transaction = Transaction::query()->lockForUpdate()->find($record->transaction_id);

            $record->delete();

            if ($transaction) {
                $transaction->lines()->delete();
                $transaction->delete();
            }
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function saveExpense(array $payload, ?Expense $expense = null, ?int $actorId = null): Expense
    {
        $resolvedActorId = $this->resolveActorId($actorId);

        return DB::transaction(function () use ($payload, $expense, $resolvedActorId): Expense {
            $attachmentIds = is_array($payload['attachment_ids'] ?? null) ? $payload['attachment_ids'] : [];
            unset($payload['attachment_ids']);

            $record = null;
            $transaction = null;

            if ($expense) {
                $record = Expense::query()->lockForUpdate()->findOrFail($expense->id);
                $transaction = Transaction::query()->lockForUpdate()->findOrFail($record->transaction_id);
            }

            if (! $transaction) {
                $transaction = Transaction::query()->create([
                    'date' => $payload['date'],
                    'type' => TransactionType::EXPENSE->value,
                    'reference_type' => null,
                    'reference_id' => null,
                    'notes' => $payload['notes'] ?? null,
                    'created_by' => $resolvedActorId,
                ]);
            }

            $record ??= new Expense();
            $record->fill($payload);
            $record->transaction_id = (int) $transaction->id;
            $record->expense_no = $record->expense_no ?: $this->generateExpenseNo();
            $record->created_by = $record->created_by ?: $resolvedActorId;
            $record->save();

            $transaction->update([
                'date' => $record->date,
                'type' => TransactionType::EXPENSE->value,
                'reference_type' => 'expense',
                'reference_id' => (int) $record->id,
                'notes' => $record->notes,
            ]);

            $this->syncTransactionLines($transaction, [
                [
                    'account_id' => (int) $record->expense_account_id,
                    'debit' => (float) $record->amount,
                    'credit' => 0,
                    'description' => 'Expense debit entry',
                ],
                [
                    'account_id' => (int) $record->payment_account_id,
                    'debit' => 0,
                    'credit' => (float) $record->amount,
                    'description' => 'Expense credit entry',
                ],
            ]);

            $this->syncAttachments($transaction, $attachmentIds, $resolvedActorId);

            return $record->refresh();
        });
    }

    public function deleteExpense(Expense $expense): void
    {
        DB::transaction(function () use ($expense): void {
            $record = Expense::query()->lockForUpdate()->findOrFail($expense->id);
            $transaction = Transaction::query()->lockForUpdate()->find($record->transaction_id);

            $record->delete();

            if ($transaction) {
                $transaction->lines()->delete();
                $transaction->delete();
            }
        });
    }

    /**
     * @param  array<int, array{account_id:int,debit:float|int,credit:float|int,description:?string}>  $lines
     */
    protected function syncTransactionLines(Transaction $transaction, array $lines): void
    {
        $normalizedLines = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($lines as $line) {
            $accountId = (int) ($line['account_id'] ?? 0);
            $debit = round(max(0, (float) ($line['debit'] ?? 0)), 3);
            $credit = round(max(0, (float) ($line['credit'] ?? 0)), 3);
            $description = $line['description'] ?? null;

            if ($accountId <= 0) {
                throw new \DomainException('Transaction line account is required.');
            }

            if ($debit > 0 && $credit > 0) {
                throw new \DomainException('Transaction line cannot have both debit and credit values.');
            }

            if ($debit <= 0 && $credit <= 0) {
                throw new \DomainException('Each transaction line must include debit or credit value.');
            }

            $normalizedLines[] = [
                'account_id' => $accountId,
                'debit' => $debit,
                'credit' => $credit,
                'description' => $description,
            ];

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        $totalDebit = round($totalDebit, 3);
        $totalCredit = round($totalCredit, 3);

        if (abs($totalDebit - $totalCredit) > 0.0001) {
            throw new \DomainException('Total debit and credit must be equal for every transaction.');
        }

        $transaction->lines()->delete();
        $transaction->lines()->createMany($normalizedLines);
    }

    protected function syncPayableIfReferenced(?string $referenceType, mixed $referenceId): void
    {
        if ($referenceType !== self::PAYABLE_REFERENCE_TYPE) {
            return;
        }

        $id = (int) $referenceId;

        if ($id <= 0) {
            return;
        }

        $this->recalculatePurchasePayable($id);
    }

    /**
     * @param  array<int, int|string>  $fileIds
     */
    protected function syncAttachments(Transaction $transaction, array $fileIds, int $actorId): void
    {
        $normalizedIds = collect($fileIds)
            ->map(static fn (mixed $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        $existingIds = $transaction->attachments()->pluck('file_id')->map(static fn (mixed $id): int => (int) $id)->all();

        $idsToDelete = array_values(array_diff($existingIds, $normalizedIds));

        if ($idsToDelete !== []) {
            $transaction->attachments()
                ->whereIn('file_id', $idsToDelete)
                ->delete();
        }

        $idsToCreate = array_values(array_diff($normalizedIds, $existingIds));

        foreach ($idsToCreate as $fileId) {
            TransactionAttachment::query()->create([
                'transaction_id' => (int) $transaction->id,
                'file_id' => (int) $fileId,
                'created_by' => $actorId,
            ]);
        }
    }

    protected function recalculatePurchasePayable(int $purchasePayableId): void
    {
        $payable = PurchasePayable::query()->lockForUpdate()->find($purchasePayableId);

        if (! $payable) {
            return;
        }

        $paidAmount = (float) Payment::query()
            ->where('reference_type', self::PAYABLE_REFERENCE_TYPE)
            ->where('reference_id', $payable->id)
            ->sum('amount');

        $latestPayment = Payment::query()
            ->where('reference_type', self::PAYABLE_REFERENCE_TYPE)
            ->where('reference_id', $payable->id)
            ->latest('id')
            ->first(['transaction_id']);

        $payable->paid_amount = round($paidAmount, 2);
        $payable->transaction_id = $latestPayment?->transaction_id;
        $payable->recalculateDueAndStatus();
        $payable->save();
    }

    protected function generatePaymentNo(): string
    {
        $lastId = (int) Payment::query()->max('id');

        return 'PAY-'.str_pad((string) ($lastId + 1), 6, '0', STR_PAD_LEFT);
    }

    protected function generateCollectionNo(): string
    {
        $lastId = (int) AccountCollection::query()->max('id');

        return 'COL-'.str_pad((string) ($lastId + 1), 6, '0', STR_PAD_LEFT);
    }

    protected function generateExpenseNo(): string
    {
        $lastId = (int) Expense::query()->max('id');

        return 'EXP-'.str_pad((string) ($lastId + 1), 6, '0', STR_PAD_LEFT);
    }

    protected function resolveActorId(?int $actorId = null): int
    {
        $resolved = $actorId ?? auth()->id();

        if (! $resolved) {
            throw new \DomainException('Unable to resolve authenticated user for this action.');
        }

        return (int) $resolved;
    }
}
