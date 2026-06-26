<?php

namespace App\Services\Accounts\Entry;

use App\Accounting\PostingContext;
use App\Enums\Accounts\EntryWorkflow;
use App\Models\AccountEntryType;
use App\Models\BankingPaymentRequest;
use App\Models\Transaction;
use App\Services\Accounts\LedgerService;
use App\Services\Accounts\PostingEngine;
use App\Services\Accounts\RequestEngine;
use Illuminate\Support\Facades\Auth;

class EntrySubmissionService
{
    public function __construct(
        private LedgerService $ledger,
        private PostingEngine $engine,
        private RequestEngine $requestEngine,
    ) {}

    public function submit(AccountEntryType $type, array $payload): mixed
    {
        return match ($type->workflow) {
            EntryWorkflow::BANKING_REQUEST => $this->requestEngine->createEntryRequest($type, $payload),
            EntryWorkflow::DIRECT_LEDGER   => $this->postToLedger($type, $payload),
            EntryWorkflow::POSTING_ENGINE  => $this->postViaEngine($type, $payload),
        };
    }

    private function postToLedger(AccountEntryType $type, array $payload): Transaction
    {
        try {
            if (isset($payload['lines']) && is_array($payload['lines'])) {
                $lines = $payload['lines'];
            } else {
                $lines = [
                    [
                        'account_id' => $payload['debit_account_id'],
                        'debit'  => $payload['amount'] ?? 0,
                        'credit' => 0,
                        'notes'  => $payload['notes'] ?? '',
                    ],
                    [
                        'account_id' => $payload['credit_account_id'],
                        'debit'  => 0,
                        'credit' => $payload['amount'] ?? 0,
                        'notes'  => $payload['notes'] ?? '',
                    ],
                ];
            }

            $header = [
                'type' => $type->transaction_type ?? 'journal',
                'method' => $payload['method'] ?? 'journal',
                'reference_type' => null,
                'reference_id' => null,
                'date' => $payload['date'] ?? now()->toDateString(),
                'name' => $payload['name'] ?? null,
                'phone' => $payload['phone'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'reference_no' => $payload['reference_no'] ?? null,
            ];

            return $this->ledger->post($header, $lines);
        } catch (\DomainException $e) {
            throw new \DomainException('Entry posting failed: ' . $e->getMessage(), 0, $e);
        }
    }

    private function postViaEngine(AccountEntryType $type, array $payload): Transaction
    {
        $context = new PostingContext(
            amount: (float) ($payload['amount'] ?? 0),
            datetime: $payload['date'] ?? now()->toDateTimeString(),
            paymentAccountId: $payload['credit_account_id'] ?? null,
            referenceNo: $payload['reference_no'] ?? null,
            method: $payload['method'] ?? null,
            name: $payload['name'] ?? null,
            phone: $payload['phone'] ?? null,
            notes: $payload['notes'] ?? null,
            actorId: Auth::id(),
            runtimeAccounts: $payload['runtime_accounts'] ?? [],
        );

        return $this->engine->record($type->accounting_event_key, $context);
    }
}
