<?php

namespace App\Enums\Accounts;

enum ReceiptPaymentGroup: string
{
    // Receipt Groups
    case CASH_RECEIPT = 'cash_receipt';
    case BANK_RECEIPT = 'bank_receipt';
    case MFS_RECEIPT = 'mfs_receipt';
    case WALLET_RECEIPT = 'wallet_receipt';
    case NON_CASH_RECEIPT = 'non_cash_receipt';

    // Payment Groups
    case CASH_PAYMENT = 'cash_payment';
    case BANK_PAYMENT = 'bank_payment';
    case MFS_PAYMENT = 'mfs_payment';
    case WALLET_PAYMENT = 'wallet_payment';
    case NON_CASH_PAYMENT = 'non_cash_payment';

    public function label(): string
    {
        return match ($this) {
            self::CASH_RECEIPT => 'Cash Receipt',
            self::BANK_RECEIPT => 'Bank Receipt',
            self::MFS_RECEIPT => 'MFS Receipt',
            self::WALLET_RECEIPT => 'Wallet Receipt',
            self::NON_CASH_RECEIPT => 'Non-Cash Receipt',

            self::CASH_PAYMENT => 'Cash Payment',
            self::BANK_PAYMENT => 'Bank Payment',
            self::MFS_PAYMENT => 'MFS Payment',
            self::WALLET_PAYMENT => 'Wallet Payment',
            self::NON_CASH_PAYMENT => 'Non-Cash Payment',
        };
    }

    public function isReceipt(): bool
    {
        return str_contains($this->value, 'receipt');
    }

    public function isPayment(): bool
    {
        return str_contains($this->value, 'payment');
    }

    public function accountType(): ?string
    {
        return match ($this) {
            self::CASH_RECEIPT, self::CASH_PAYMENT => 'cash',
            self::BANK_RECEIPT, self::BANK_PAYMENT => 'bank',
            self::MFS_RECEIPT, self::MFS_PAYMENT => 'mfs',
            self::WALLET_RECEIPT, self::WALLET_PAYMENT => 'wallet',
            self::NON_CASH_RECEIPT, self::NON_CASH_PAYMENT => null,
        };
    }
}
