<?php

namespace App\Enums\Accounts;

use Illuminate\Support\Facades\App;

enum AccountSubType: string
{
    // =====================
    // ASSETS
    // =====================
    case CASH = 'cash';
    case BANK = 'bank';
    case MFS = 'mfs';
    case WALLET = 'wallet';

    // =====================
    // HELPERS
    // =====================
    public static function assetTypes(): array
    {
        return [
            self::CASH,
            self::BANK,
            self::MFS,
            self::WALLET
        ];
    }
    public static function referenceModels(): array
    {
        return [
            self::BANK => \App\Models\BankAccount::class,
        ];
    }
    
    public static function modelClass(string $subType): ?string
    {
        return self::referenceModels()[$subType] ?? null;
    }

    public static function all(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    // =====================
    // LABELS
    // =====================
    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::BANK => 'Bank',
            self::MFS => 'MFS',
            self::WALLET => 'Wallet',

        };
    }

    // Optional helper for dropdowns
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(function ($case) {
            return [$case->value => $case->label()];
        })->toArray();
    }
}
