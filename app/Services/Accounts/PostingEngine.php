<?php

namespace App\Services\Accounts;

use App\Accounting\PostingContext;
use App\Enums\Accounts\PostingLeg;
use App\Models\AccountingEvent;
use App\Models\PostingRule;
use App\Models\Transaction;

/**
 * Resolves a configured accounting event's posting rules against runtime context
 * into balanced ledger lines, then delegates to LedgerService — the single writer.
 *
 * The engine is intentionally thin: it does account resolution and amount mapping
 * only; balance enforcement and persistence stay in LedgerService.
 */
class PostingEngine
{
    public function __construct(private readonly LedgerService $ledger) {}

    /**
     * Post the journal for a business event.
     */
    public function record(string $eventKey, PostingContext $context): Transaction
    {
        $event = AccountingEvent::query()
            ->active()
            ->forKey($eventKey)
            ->with(['rules.account:id,name,is_active'])
            ->first();

        if (! $event) {
            throw new \DomainException("No active accounting event configured for: {$eventKey}");
        }

        if ($event->rules->isEmpty()) {
            throw new \DomainException("Accounting event '{$eventKey}' has no posting rules configured.");
        }

        if (! $event->isBalancedRecipe()) {
            throw new \DomainException("Accounting event '{$eventKey}' must have at least one debit and one credit leg.");
        }

        $amount = round($context->amount, 2);

        if ($amount <= 0) {
            throw new \DomainException('Posting amount must be greater than zero.');
        }

        $lines = $event->rules->map(
            fn (PostingRule $rule): array => $this->resolveLine($rule, $context, $amount, $eventKey)
        )->all();

        // Strip null values so columns with DB defaults (e.g. `method`) keep them
        // instead of being forced to NULL.
        $header = array_filter([
            'datetime'       => $context->datetime,
            'type'           => $event->transaction_type->value,
            'reference_type' => $context->referenceType,
            'reference_id'   => $context->referenceId,
            'reference_no'   => $context->referenceNo,
            'method'         => $context->method,
            'name'           => $context->name,
            'phone'          => $context->phone,
            'notes'          => $context->notes,
            'created_by'     => $context->actorId,
        ], static fn ($value): bool => $value !== null);

        // LedgerService::post opens the DB transaction and enforces balance.
        return $this->ledger->post($header, $lines);
    }

    /**
     * @return array{account_id:int, debit:float, credit:float, notes:?string}
     */
    private function resolveLine(PostingRule $rule, PostingContext $context, float $amount, string $eventKey): array
    {
        $accountId = $this->resolveAccountId($rule, $context, $eventKey);

        $isDebit = $rule->leg === PostingLeg::DEBIT;

        return [
            'account_id' => $accountId,
            'debit'      => $isDebit ? $amount : 0.0,
            'credit'     => $isDebit ? 0.0 : $amount,
            'notes'      => $rule->description,
        ];
    }

    private function resolveAccountId(PostingRule $rule, PostingContext $context, string $eventKey): int
    {
        if ($rule->isFixed()) {
            $accountId = (int) $rule->account_id;

            if ($accountId <= 0) {
                throw new \DomainException("Event '{$eventKey}': a fixed posting leg has no account configured.");
            }

            if ($rule->relationLoaded('account') && $rule->account && ! $rule->account->is_active) {
                throw new \DomainException("Event '{$eventKey}': configured account '{$rule->account->name}' is inactive.");
            }

            return $accountId;
        }

        // Runtime leg — resolve from the context (user-selected payment account).
        $accountId = $context->runtimeAccountId($rule->runtime_slot);

        if (! $accountId) {
            $slot = $rule->runtime_slot ?: 'payment_account';
            throw new \DomainException("Event '{$eventKey}': no runtime account supplied for slot '{$slot}'.");
        }

        return (int) $accountId;
    }
}
