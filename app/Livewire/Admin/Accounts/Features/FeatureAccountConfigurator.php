<?php

namespace App\Livewire\Admin\Accounts\Features;

use App\Models\Account;
use App\Models\Feature;
use App\Models\FeatureAccountMapping;
use App\Services\Accounts\FeatureAccountService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class FeatureAccountConfigurator extends Component
{
    public ?string $selectedFeature = null;

    public array $enabledMappings = [];

    // Add-feature form
    public bool $showAddFeature = false;
    public string $newFeatureLabel = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('accounts.chart.list'), 403);
    }

    private function service(): FeatureAccountService
    {
        return app(FeatureAccountService::class);
    }

    public function selectFeature(string $featureKey): void
    {
        $this->selectedFeature = $featureKey;
        $this->loadEnabledMappings();
    }

    private function loadEnabledMappings(): void
    {
        if (!$this->selectedFeature) {
            $this->enabledMappings = [];
            return;
        }

        if (!Feature::where('key', $this->selectedFeature)->exists()) {
            $this->enabledMappings = [];
            return;
        }

        $mappings = FeatureAccountMapping::where('feature_key', $this->selectedFeature)
            ->where('is_enabled', true)
            ->pluck('child_account_id')
            ->toArray();

        $this->enabledMappings = array_map('strval', $mappings);
    }

    public function createFeature(): void
    {
        $this->validate([
            'newFeatureLabel' => 'required|string|max:120',
        ]);

        $key = Str::snake(Str::lower(trim($this->newFeatureLabel)));

        if ($key === '') {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Invalid feature name.']);
            return;
        }

        if (Feature::where('key', $key)->exists()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'A feature with this name already exists.']);
            return;
        }

        Feature::create([
            'key' => $key,
            'label' => trim($this->newFeatureLabel),
            'is_locked' => false,
            'is_active' => true,
            'sort_order' => (int) Feature::max('sort_order') + 1,
        ]);

        $this->newFeatureLabel = '';
        $this->showAddFeature = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Feature added.']);
    }

    public function deleteFeature(int $featureId): void
    {
        $feature = Feature::find($featureId);

        if (!$feature) {
            return;
        }

        if ($feature->isLocked()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'System features cannot be deleted.']);
            return;
        }

        // Remove its account mappings, then the feature.
        FeatureAccountMapping::where('feature_key', $feature->key)->delete();

        if ($this->selectedFeature === $feature->key) {
            $this->selectedFeature = null;
            $this->enabledMappings = [];
        }

        $feature->delete();

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Feature deleted.']);
    }

    public function toggleFeatureActive(int $featureId): void
    {
        $feature = Feature::find($featureId);

        if (!$feature) {
            return;
        }

        $feature->update(['is_active' => !$feature->is_active]);
    }

    public function toggleChild(int $childId): void
    {
        if (!$this->selectedFeature) {
            return;
        }

        $childIdStr = (string) $childId;

        if (in_array($childIdStr, $this->enabledMappings)) {
            $this->enabledMappings = array_filter(
                $this->enabledMappings,
                fn ($id) => $id !== $childIdStr
            );
        } else {
            $this->enabledMappings[] = $childIdStr;
        }
    }

    public function toggleParent(int $parentId): void
    {
        if (!$this->selectedFeature) {
            return;
        }

        // Get all descendant leaf accounts for this parent
        $leafChildIds = $this->getAllLeafChildren($parentId);

        // Check if all children are currently enabled
        $allEnabled = count(array_intersect(
            array_map('strval', $leafChildIds),
            $this->enabledMappings
        )) === count($leafChildIds);

        if ($allEnabled) {
            // Disable all children
            $this->enabledMappings = array_filter(
                $this->enabledMappings,
                fn ($id) => !in_array((int)$id, $leafChildIds)
            );
        } else {
            // Enable all children
            foreach ($leafChildIds as $leafId) {
                $leafIdStr = (string)$leafId;
                if (!in_array($leafIdStr, $this->enabledMappings)) {
                    $this->enabledMappings[] = $leafIdStr;
                }
            }
        }
    }

    private function getAllLeafChildren(int $accountId): array
    {
        $allAccounts = Account::where('is_active', true)->get();
        return $this->findLeafChildren($accountId, $allAccounts);
    }

    private function findLeafChildren(int $parentId, $allAccounts): array
    {
        $leafIds = [];
        $children = $allAccounts->where('parent_id', $parentId);

        foreach ($children as $child) {
            $hasGrandchildren = $allAccounts->where('parent_id', $child->id)->count() > 0;

            if ($hasGrandchildren) {
                // Recursively find leaf children
                $leafIds = array_merge($leafIds, $this->findLeafChildren($child->id, $allAccounts));
            } else {
                // This is a leaf node
                $leafIds[] = $child->id;
            }
        }

        return $leafIds;
    }

    public function saveAll(): void
    {
        if (!$this->selectedFeature) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Please select a feature first.']);
            return;
        }

        if (!Feature::where('key', $this->selectedFeature)->exists()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Invalid feature selected.']);
            return;
        }

        $featureKey = $this->selectedFeature;
        $enabledChildIds = array_map('intval', $this->enabledMappings);

        // Delete all existing mappings for this feature
        FeatureAccountMapping::where('feature_key', $featureKey)->delete();

        // Get all accounts and their parents
        $allAccounts = Account::where('is_active', true)->get();

        // Create mappings for each enabled child account
        foreach ($enabledChildIds as $childId) {
            $child = $allAccounts->find($childId);

            if ($child && $child->parent_id) {
                // Get the top-level parent (root) of this account
                $parent = $child;
                while ($parent->parent_id) {
                    $parent = $allAccounts->find($parent->parent_id);
                }

                // Create mapping with the root parent
                FeatureAccountMapping::create([
                    'feature_key' => $featureKey,
                    'parent_account_id' => $parent->id,
                    'child_account_id' => $childId,
                    'is_enabled' => true,
                ]);
            }
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Feature account preferences saved.']);
    }

    private function buildAccountTree($accounts = null, $parentId = null)
    {
        $tree = [];

        if ($accounts === null) {
            $accounts = Account::where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        foreach ($accounts as $account) {
            if ($account->parent_id === $parentId) {
                $children = $this->buildAccountTree($accounts, $account->id);
                $tree[] = [
                    'id' => $account->id,
                    'name' => $account->name,
                    'code' => $account->code,
                    'children' => $children,
                ];
            }
        }

        return $tree;
    }

    public function render(): View
    {
        $features = Feature::ordered()->get();

        $accountTree = [];
        if ($this->selectedFeature) {
            $allAccounts = Account::where('is_active', true)
                ->orderBy('name')
                ->get();

            $accountTree = $this->buildAccountTree($allAccounts, null);
        }

        return view('livewire.admin.accounts.features.feature-account-configurator', [
            'features' => $features,
            'accountTree' => $accountTree,
        ])->layout('layouts.admin.admin');
    }
}
