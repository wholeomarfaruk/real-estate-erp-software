<?php

namespace App\Livewire\Admin\Accounts\Concerns;

use App\Services\Accounts\FeatureAccountService;
use Illuminate\Support\Collection;

trait InteractsWithFeatureAccounts
{
    protected function featureAccountService(): FeatureAccountService
    {
        return app(FeatureAccountService::class);
    }

    protected function getParentAccountsForFeature(string $featureKey): Collection
    {
        return $this->featureAccountService()->getParentAccountsForFeature($featureKey);
    }

    protected function getEnabledChildrenForParent(string $featureKey, int $parentAccountId): Collection
    {
        return $this->featureAccountService()->getEnabledChildrenForParent($featureKey, $parentAccountId);
    }

    protected function getAllEnabledChildrenForFeature(string $featureKey): Collection
    {
        return $this->featureAccountService()->getAllEnabledChildrenForFeature($featureKey);
    }
}
