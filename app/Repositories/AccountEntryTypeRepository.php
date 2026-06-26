<?php

namespace App\Repositories;

use App\DTOs\Accounts\CategoryData;
use App\Models\AccountEntryCategory;
use App\Models\AccountEntryType;
use Illuminate\Support\Collection;

class AccountEntryTypeRepository
{
    public function getCategorized(): array
    {
        $categories = AccountEntryCategory::active()->ordered()->get();
        $result = [];

        foreach ($categories as $category) {
            $types = $category->types()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            $result[$category->key] = new CategoryData(
                key: $category->key,
                title: $category->title,
                description: $category->description,
                icon: $category->icon ?? '',
                sort: $category->sort_order,
                items: $types,
            );
        }

        uasort($result, fn ($a, $b) => $a->sort <=> $b->sort);

        return $result;
    }

    public function findBySlug(string $slug): ?AccountEntryType
    {
        return AccountEntryType::where('slug', $slug)->where('is_active', true)->first();
    }

    public function findCategory(string $key): ?CategoryData
    {
        $category = AccountEntryCategory::where('key', $key)->where('is_active', true)->first();
        if (!$category) {
            return null;
        }

        $types = $category->types()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return new CategoryData(
            key: $category->key,
            title: $category->title,
            description: $category->description,
            icon: $category->icon ?? '',
            sort: $category->sort_order,
            items: $types,
        );
    }

    public function allActive(): Collection
    {
        return AccountEntryType::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}
