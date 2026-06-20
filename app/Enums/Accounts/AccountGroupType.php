<?php

namespace App\Enums\Accounts;

/**
 * Top-level accounting classification for a chart-of-accounts entry.
 *
 * | Group     | Purpose                    |
 * | --------- | -------------------------- |
 * | Asset     | কোম্পানির সম্পদ            |
 * | Liability | কোম্পানির দায়             |
 * | Equity    | মালিকের মূলধন/সংরক্ষিত আয় |
 * | Income    | আয়                        |
 * | Expense   | ব্যয়                      |
 *
 * This is distinct from {@see AccountType} (cash/bank/mfs/wallet/ledger), which
 * describes the *kind* of account; the group describes where it sits in the
 * accounting equation (Assets = Liabilities + Equity; Income − Expense = Profit).
 */
enum AccountGroupType: string
{
    case ASSET     = 'asset';
    case LIABILITY = 'liability';
    case EQUITY    = 'equity';
    case INCOME    = 'income';
    case EXPENSE   = 'expense';

    /** English label for UI. */
    public function label(): string
    {
        return match ($this) {
            self::ASSET     => 'Asset',
            self::LIABILITY => 'Liability',
            self::EQUITY    => 'Equity',
            self::INCOME    => 'Income',
            self::EXPENSE   => 'Expense',
        };
    }

    /** Bangla purpose / description. */
    public function purpose(): string
    {
        return match ($this) {
            self::ASSET     => 'কোম্পানির সম্পদ',
            self::LIABILITY => 'কোম্পানির দায়',
            self::EQUITY    => 'মালিকের মূলধন/সংরক্ষিত আয়',
            self::INCOME    => 'আয়',
            self::EXPENSE   => 'ব্যয়',
        };
    }

    /**
     * Normal balance side: assets & expenses are debit-normal,
     * liabilities, equity & income are credit-normal.
     */
    public function normalBalance(): string
    {
        return match ($this) {
            self::ASSET, self::EXPENSE => 'debit',
            self::LIABILITY, self::EQUITY, self::INCOME => 'credit',
        };
    }

    /**
     * @return array<string, string> value => label, for dropdowns.
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
