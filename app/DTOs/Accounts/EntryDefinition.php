<?php

namespace App\DTOs\Accounts;

use App\Enums\Accounts\EntryWorkflow;
use App\Enums\Accounts\TransactionType;

readonly class EntryDefinition
{
    public function __construct(
        public string $slug,
        public string $categoryKey,
        public string $title,
        public string $description,
        public ?string $componentClass,
        public EntryWorkflow $workflow,
        public TransactionType $transactionType,
        public string $permission,
        public bool $enabled,
        public bool $visible,
        public int $sort,
        public ?array $routeOverride,
        public string $icon,
    ) {}
}
