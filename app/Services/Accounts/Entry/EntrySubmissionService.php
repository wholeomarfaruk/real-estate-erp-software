<?php

namespace App\Services\Accounts\Entry;

use App\DTOs\Accounts\EntryDefinition;
use App\Enums\Accounts\EntryWorkflow;
use App\Models\BankingPaymentRequest;
use App\Models\Transaction;
use App\Services\Accounts\LedgerService;
use Illuminate\Support\Facades\Auth;

class EntrySubmissionService
{
    public function __construct(
        private LedgerService $ledger,
    ) {}

    public function submit(EntryDefinition $def, array $payload): BankingPaymentRequest|Transaction
    {
        return match ($def->workflow) {
            EntryWorkflow::BANKING_REQUEST => $this->postToBankingRequest($def, $payload),
            EntryWorkflow::DIRECT_LEDGER   => $this->postToLedger($def, $payload),
        };
    }

    private function postToBankingRequest(EntryDefinition $def, array $payload): BankingPaymentRequest
    {
        $attachments = $payload['attachments'] ?? [];
        $externalData = [
            'debit_account_id' => $payload['debit_account_id'],
            'credit_account_id' => $payload['credit_account_id'],
            'method' => $payload['method'] ?? 'cash',
            'reference_no' => $payload['reference_no'] ?? null,
            'name' => $payload['name'] ?? null,
            'phone' => $payload['phone'] ?? null,
        ];

        if (!empty($attachments)) {
            $externalData['attachments'] = $attachments;
        }

        $bpr = BankingPaymentRequest::create([
            'request_no' => BankingPaymentRequest::generateRequestNo(),
            'source_type' => $def->transactionType->value,
            'transaction_category_id' => null,
            'amount' => round((float) $payload['amount'], 3),
            'description' => $payload['description'] ?? '',
            'account_id' => $payload['credit_account_id'],
            'status' => 'pending',
            'notes' => $payload['notes'] ?? null,
            'reference_no' => $payload['reference_no'] ?? null,
            'name' => $payload['name'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'method' => $payload['method'] ?? 'cash',
            'requested_by' => Auth::id(),
            'external_data' => $externalData,
            'debit_account_id' => $payload['debit_account_id'],
            'debit_amount' => round((float) $payload['amount'], 3),
            'credit_account_id' => $payload['credit_account_id'],
            'credit_amount' => round((float) $payload['amount'], 3),
        ]);

        return $bpr;
    }

    private function postToLedger(EntryDefinition $def, array $payload): Transaction
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
                'type' => $def->transactionType->value,
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
}
