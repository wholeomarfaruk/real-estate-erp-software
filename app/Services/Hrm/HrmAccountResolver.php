<?php

namespace App\Services\Hrm;

use App\Models\Account;

class HrmAccountResolver
{
    public function resolveRequiredAccount(string $key): Account
    {
        $configured = config("hrm.accounts.$key");

        if (! is_array($configured)) {
            throw new \DomainException("HRM account mapping not configured for: $key");
        }

        $query = Account::query()->where('is_active', true);

        $code = trim((string) ($configured['code'] ?? ''));
        $name = trim((string) ($configured['name'] ?? ''));

        if ($code !== '') {
            $account = (clone $query)->where('code', $code)->first();

            if ($account) {
                return $account;
            }
        }

        if ($name !== '') {
            $account = (clone $query)->where('name', $name)->first();

            if ($account) {
                return $account;
            }
        }

        throw new \DomainException("Required HRM account not found for key: $key");
    }

    public function resolvePaymentAccountByMethod(?string $method): Account
    {
        $normalizedMethod = strtolower(trim((string) $method));
        $mapping = config('hrm.payment_method_account', []);
        $mappedKey = $mapping[$normalizedMethod] ?? $mapping['cash'] ?? 'cash';

        return $this->resolveRequiredAccount($mappedKey);
    }
}

