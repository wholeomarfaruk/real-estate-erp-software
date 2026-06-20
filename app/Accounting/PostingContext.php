<?php

namespace App\Accounting;

/**
 * Runtime data a module passes to the PostingEngine for one accounting event.
 *
 * Carries the event amount, header metadata, and the account(s) the user picked
 * at runtime (e.g. the cash/bank account money was received into / paid from),
 * which fill the event's `runtime` legs.
 */
class PostingContext
{
    /**
     * @param  array<string, int>  $runtimeAccounts  slot => account_id overrides
     *         (e.g. ['payment_account' => 12]). When a runtime leg's slot is not
     *         present here, the engine falls back to $paymentAccountId.
     * @param  array<string, mixed>  $extra  reserved for future amount/context legs.
     */
    public function __construct(
        public readonly float $amount,
        public readonly string $datetime,
        public readonly ?int $paymentAccountId = null,
        public readonly ?string $referenceType = null,
        public readonly ?int $referenceId = null,
        public readonly ?string $referenceNo = null,
        public readonly ?string $method = null,
        public readonly ?string $name = null,
        public readonly ?string $phone = null,
        public readonly ?string $notes = null,
        public readonly ?int $actorId = null,
        public readonly array $runtimeAccounts = [],
        public readonly array $extra = [],
    ) {}

    /**
     * Resolve the account id for a runtime slot: explicit override first, then
     * the single payment account, else null (engine raises a clear error).
     */
    public function runtimeAccountId(?string $slot): ?int
    {
        if ($slot !== null && array_key_exists($slot, $this->runtimeAccounts)) {
            return (int) $this->runtimeAccounts[$slot];
        }

        return $this->paymentAccountId;
    }
}
