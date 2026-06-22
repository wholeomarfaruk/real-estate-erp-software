<?php

namespace App\Livewire\Admin\Accounts\Concerns;

use App\Enums\Accounts\FeatureType;
use App\Services\Accounts\FeatureAccountService;
use Illuminate\Support\Collection;

trait InteractsWithFeatureAccounts
{
    protected function featureAccountService(): FeatureAccountService
    {
        return app(FeatureAccountService::class);
    }

    protected function getParentAccountsForFeature(FeatureType $feature): Collection
    {
        return $this->featureAccountService()->getParentAccountsForFeature($feature);
    }

    protected function getEnabledChildrenForParent(FeatureType $feature, int $parentAccountId): Collection
    {
        return $this->featureAccountService()->getEnabledChildrenForParent($feature, $parentAccountId);
    }

    protected function getAllEnabledChildrenForFeature(FeatureType $feature): Collection
    {
        return $this->featureAccountService()->getAllEnabledChildrenForFeature($feature);
    }
}
