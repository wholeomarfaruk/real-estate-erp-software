<?php

namespace App\Enums\Property;

enum PaymentCategory: string
{
    case DOWN_PAYMENT     = 'down_payment';
    case INSTALLMENT      = 'installment';
    case MONTHLY_RENT     = 'monthly_rent';
    case SECURITY_DEPOSIT = 'security_deposit';
    case EXTRA_CHARGE     = 'extra_charge';
    case MANUAL_CHARGE    = 'manual_charge';

    public function label(): string
    {
        return match($this) {
            self::DOWN_PAYMENT     => 'Down Payment',
            self::INSTALLMENT      => 'Installment',
            self::MONTHLY_RENT     => 'Monthly Rent',
            self::SECURITY_DEPOSIT => 'Security Deposit',
            self::EXTRA_CHARGE     => 'Extra Charge',
            self::MANUAL_CHARGE    => 'Manual Charge',
        };
    }

    public function isAutoGenerable(): bool
    {
        return in_array($this, [self::INSTALLMENT, self::MONTHLY_RENT, self::SECURITY_DEPOSIT, self::DOWN_PAYMENT]);
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
