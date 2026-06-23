<?php

namespace App\Enums\Accounts;

enum EntryMethod: string
{
    case CASH = 'cash';
    case BANK = 'bank';
    case CHEQUE = 'cheque';
    case BKASH = 'bkash';
    case NAGAD = 'nagad';
    case ROCKET = 'rocket';
    case CARD = 'card';
    case BANK_TRANSFER = 'bank_transfer';
    case JOURNAL = 'journal';
    case ADJUSTMENT = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::BANK => 'Bank',
            self::CHEQUE => 'Cheque',
            self::BKASH => 'bKash',
            self::NAGAD => 'Nagad',
            self::ROCKET => 'Rocket',
            self::CARD => 'Card',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::JOURNAL => 'Journal Entry',
            self::ADJUSTMENT => 'Adjustment',
        };
    }

    // ─── Basic Classification ───────────────────────────────────────────────

    public function isCash(): bool
    {
        return $this === self::CASH;
    }

    public function isBank(): bool
    {
        return in_array($this, [self::BANK, self::CHEQUE, self::BANK_TRANSFER]);
    }

    public function isOnline(): bool
    {
        return in_array($this, [
            self::BKASH,
            self::NAGAD,
            self::ROCKET,
            self::CARD,
            self::BANK_TRANSFER,
        ]);
    }

    public function isDigital(): bool
    {
        return in_array($this, [
            self::BKASH,
            self::NAGAD,
            self::ROCKET,
            self::CARD,
        ]);
    }

    public function isMFS(): bool
    {
        return in_array($this, [self::BKASH, self::NAGAD, self::ROCKET]);
    }

    public function isJournalEntry(): bool
    {
        return $this === self::JOURNAL;
    }

    public function isAdjustment(): bool
    {
        return $this === self::ADJUSTMENT;
    }

    // ─── Account Type ────────────────────────────────────────────────────────

    /**
     * Which account type this entry method belongs to
     * Maps:
     * - CASH → CASH
     * - BANK, CHEQUE, BANK_TRANSFER → BANK
     * - BKASH, NAGAD, ROCKET → MFS
     * - CARD → WALLET
     * - JOURNAL, ADJUSTMENT → LEDGER (non-account entries)
     */
    public function accountType(): AccountType
    {
        return match ($this) {
            self::CASH => AccountType::CASH,
            self::BANK, self::CHEQUE, self::BANK_TRANSFER => AccountType::BANK,
            self::BKASH, self::NAGAD, self::ROCKET => AccountType::MFS,
            self::CARD => AccountType::WALLET,
            self::JOURNAL, self::ADJUSTMENT => AccountType::LEDGER,
        };
    }
}
