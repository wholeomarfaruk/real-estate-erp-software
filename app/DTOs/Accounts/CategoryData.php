<?php

namespace App\DTOs\Accounts;

use Illuminate\Support\Collection;

readonly class CategoryData
{
    /**
     * @param  Collection<int, EntryDefinition>  $items
     */
    public function __construct(
        public string $key,
        public string $title,
        public string $description,
        public string $icon,
        public int $sort,
        public Collection $items,
    ) {}
}
