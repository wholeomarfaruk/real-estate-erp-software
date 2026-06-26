<?php

namespace App\Services\Accounts\Entry;

use App\DTOs\Accounts\EntryDefinition;
use App\Enums\Accounts\EntryWorkflow;
use App\Enums\Accounts\TransactionType;
use Illuminate\Support\Collection;

class ConfigBasedEntryRegistry
{
    private static ?array $cache = null;

    public function getCategorized(): array
    {
        $config = $this->getConfig();
        $categories = $config['categories'] ?? [];
        $entries = $config['entries'] ?? [];

        $categorized = [];

        foreach ($categories as $key => $category) {
            $categoryEntries = [];
            foreach ($entries as $slug => $entry) {
                if ($entry['category'] === $key) {
                    $categoryEntries[$slug] = $this->toDefinition($slug, $entry);
                }
            }

            uasort($categoryEntries, fn ($a, $b) => $a->sort <=> $b->sort);

            $categorized[$key] = [
                'key' => $key,
                'title' => $category['title'] ?? '',
                'description' => $category['description'] ?? '',
                'icon' => $category['icon'] ?? '',
                'sort' => $category['sort'] ?? 0,
                'items' => array_values($categoryEntries),
            ];
        }

        uasort($categorized, fn ($a, $b) => ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0));

        return $categorized;
    }

    public function find(string $slug): ?EntryDefinition
    {
        $entries = $this->getConfig()['entries'] ?? [];

        if (!isset($entries[$slug])) {
            return null;
        }

        return $this->toDefinition($slug, $entries[$slug]);
    }

    public function getCategory(string $key): ?array
    {
        $categorized = $this->getCategorized();
        return $categorized[$key] ?? null;
    }

    public function all(): Collection
    {
        $config = $this->getConfig();
        $entries = $config['entries'] ?? [];

        return collect($entries)
            ->map(fn ($entry, $slug) => $this->toDefinition($slug, $entry))
            ->values();
    }

    public function exists(string $slug): bool
    {
        return isset($this->getConfig()['entries'][$slug]);
    }

    private function toDefinition(string $slug, array $entry): EntryDefinition
    {
        return new EntryDefinition(
            slug: $slug,
            categoryKey: $entry['category'] ?? '',
            title: $entry['title'] ?? '',
            description: $entry['description'] ?? '',
            componentClass: $entry['component'] ?? null,
            workflow: $entry['workflow'] instanceof EntryWorkflow
                ? $entry['workflow']
                : EntryWorkflow::from($entry['workflow']),
            transactionType: $entry['transaction_type'] instanceof TransactionType
                ? $entry['transaction_type']
                : TransactionType::from($entry['transaction_type']),
            permission: $entry['permission'] ?? 'accounts.entry.create',
            enabled: $entry['enabled'] ?? true,
            visible: $entry['visible'] ?? true,
            sort: $entry['sort'] ?? 0,
            routeOverride: $entry['route'] ?? null,
            icon: $entry['icon'] ?? '',
        );
    }

    private function getConfig(): array
    {
        if (self::$cache === null) {
            self::$cache = config('account-entries', []);
        }

        return self::$cache;
    }
}
