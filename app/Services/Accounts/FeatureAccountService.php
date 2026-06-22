<?php

namespace App\Services\Accounts;

use App\Enums\Accounts\FeatureType;
use App\Models\Account;
use App\Models\FeatureAccountMapping;
use Illuminate\Support\Collection;

class FeatureAccountService
{
    public function getParentAccountsForFeature(FeatureType $feature): Collection
    {
        return FeatureAccountMapping::where('feature_key', $feature->value)
            ->where('is_enabled', true)
            ->distinct('parent_account_id')
            ->with('parentAccount')
            ->get()
            ->pluck('parentAccount')
            ->unique('id');
    }

    public function getEnabledChildrenForParent(FeatureType $feature, int $parentAccountId): Collection
    {
        return FeatureAccountMapping::where('feature_key', $feature->value)
            ->where('parent_account_id', $parentAccountId)
            ->where('is_enabled', true)
            ->orderBy('sort_order')
            ->with('childAccount')
            ->get()
            ->pluck('childAccount');
    }

    public function getAllEnabledChildrenForFeature(FeatureType $feature): Collection
    {
        return FeatureAccountMapping::where('feature_key', $feature->value)
            ->where('is_enabled', true)
            ->orderBy('sort_order')
            ->with('childAccount')
            ->get()
            ->pluck('childAccount');
    }

    public function toggleChildForFeature(FeatureType $feature, int $parentId, int $childId, bool $enabled): void
    {
        FeatureAccountMapping::where('feature_key', $feature->value)
            ->where('parent_account_id', $parentId)
            ->where('child_account_id', $childId)
            ->update(['is_enabled' => $enabled]);
    }

    public function updateEnabledChildrenForFeatureAndParent(
        FeatureType $feature,
        int $parentId,
        array $enabledChildIds
    ): void {
        FeatureAccountMapping::where('feature_key', $feature->value)
            ->where('parent_account_id', $parentId)
            ->update(['is_enabled' => false]);

        if (!empty($enabledChildIds)) {
            FeatureAccountMapping::where('feature_key', $feature->value)
                ->where('parent_account_id', $parentId)
                ->whereIn('child_account_id', $enabledChildIds)
                ->update(['is_enabled' => true]);
        }
    }

    public function initializeFeatureForParentAccount(FeatureType $feature, int $parentAccountId): void
    {
        $parent = Account::findOrFail($parentAccountId);

        if (!$parent->children()->exists()) {
            return;
        }

        foreach ($parent->children as $child) {
            FeatureAccountMapping::firstOrCreate(
                [
                    'feature_key' => $feature->value,
                    'parent_account_id' => $parentAccountId,
                    'child_account_id' => $child->id,
                ],
                ['is_enabled' => true]
            );
        }
    }

    public function initializeFeatureForAllApplicableParents(FeatureType $feature, string $accountGroup): void
    {
        $parents = Account::where('group', $accountGroup)
            ->whereNull('parent_id')
            ->get();

        foreach ($parents as $parent) {
            $this->initializeFeatureForParentAccount($feature, $parent->id);
        }
    }
}
