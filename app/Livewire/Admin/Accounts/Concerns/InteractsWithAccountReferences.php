<?php

namespace App\Livewire\Admin\Accounts\Concerns;

use App\Models\Account;

trait InteractsWithAccountReferences
{
    /**
     * @return array<int, string>
     */
    protected function configuredReferenceKeys(): array
    {
        return array_keys(account_reference_config());
    }

    /**
     * @return array<string, string>
     */
    protected function referenceOptionsForAccount(?int $accountId): array
    {
        if (! $accountId) {
            return [];
        }

        $account = Account::query()->find($accountId);

        if (! $account) {
            return [];
        }

        return $account->allowedReferences()
            ->mapWithKeys(static fn (array $reference, string $key): array => [$key => (string) ($reference['label'] ?? $key)])
            ->all();
    }

    protected function resetReferenceSelectionIfUnavailable(
        ?int $accountId,
        string $referenceTypeProperty = 'reference_type',
        string $referenceIdProperty = 'reference_id'
    ): void {
        $referenceType = $this->{$referenceTypeProperty} ?? null;

        if (! is_string($referenceType) || $referenceType === '') {
            return;
        }

        if (array_key_exists($referenceType, $this->referenceOptionsForAccount($accountId))) {
            return;
        }

        $this->{$referenceTypeProperty} = null;

        if (property_exists($this, $referenceIdProperty)) {
            $this->{$referenceIdProperty} = null;
        }
    }

    protected function selectedReferenceTypeIsAllowed(?int $accountId, string $referenceTypeProperty = 'reference_type'): bool
    {
        $referenceType = $this->{$referenceTypeProperty} ?? null;

        if (! is_string($referenceType) || $referenceType === '') {
            return true;
        }

        if (array_key_exists($referenceType, $this->referenceOptionsForAccount($accountId))) {
            return true;
        }

        $this->addError($referenceTypeProperty, 'Selected reference type is not allowed for the chosen account.');

        return false;
    }
}
