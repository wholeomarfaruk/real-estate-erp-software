<?php

namespace App\Enums\Notification;

enum NotificationType: string
{
    case INVOICE_DUE       = 'invoice_due';
    case INVOICE_PAID      = 'invoice_paid';
    case PAYMENT_RECEIVED  = 'payment_received';
    case LEAVE_APPROVED    = 'leave_approved';
    case SALARY_GENERATED  = 'salary_generated';
    case ATTENDANCE        = 'attendance';
    case OFFER             = 'offer';
    case NEWS              = 'news';
    case BLOG              = 'blog';
    case SYSTEM            = 'system';
    case WELCOME           = 'welcome';
    case REMINDER          = 'reminder';

    public function label(): string
    {
        return match ($this) {
            self::INVOICE_DUE      => 'Invoice Due',
            self::INVOICE_PAID     => 'Invoice Paid',
            self::PAYMENT_RECEIVED => 'Payment Received',
            self::LEAVE_APPROVED   => 'Leave Approved',
            self::SALARY_GENERATED => 'Salary Generated',
            self::ATTENDANCE       => 'Attendance',
            self::OFFER            => 'Offer',
            self::NEWS             => 'News',
            self::BLOG             => 'Blog',
            self::SYSTEM           => 'System',
            self::WELCOME          => 'Welcome',
            self::REMINDER         => 'Reminder',
        };
    }

    public function reportGroup(): NotificationGroup
    {
        return match ($this) {
            self::INVOICE_DUE,
            self::INVOICE_PAID,
            self::PAYMENT_RECEIVED,
            self::SALARY_GENERATED
            => NotificationGroup::FINANCIAL,

            self::LEAVE_APPROVED,
            self::ATTENDANCE
            => NotificationGroup::HR,

            self::OFFER,
            self::NEWS,
            self::BLOG
            => NotificationGroup::MARKETING,

            self::SYSTEM,
            self::WELCOME,
            self::REMINDER
            => NotificationGroup::SYSTEM,
        };
    }

    public function defaultBadgeClass(): string
    {
        return match ($this) {
            self::INVOICE_DUE      => 'bg-amber-50 text-amber-700 border-amber-200',
            self::INVOICE_PAID     => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::PAYMENT_RECEIVED => 'bg-teal-50 text-teal-700 border-teal-200',
            self::LEAVE_APPROVED   => 'bg-blue-50 text-blue-700 border-blue-200',
            self::SALARY_GENERATED => 'bg-pink-50 text-pink-700 border-pink-200',
            self::ATTENDANCE       => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            self::OFFER            => 'bg-violet-50 text-violet-700 border-violet-200',
            self::NEWS             => 'bg-cyan-50 text-cyan-700 border-cyan-200',
            self::BLOG              => 'bg-purple-50 text-purple-700 border-purple-200',
            self::SYSTEM            => 'bg-gray-100 text-gray-600 border-gray-200',
            self::WELCOME           => 'bg-lime-50 text-lime-700 border-lime-200',
            self::REMINDER          => 'bg-orange-50 text-orange-700 border-orange-200',
        };
    }

    public static function financial(): array
    {
        return collect(self::cases())
            ->filter(fn ($type) => $type->reportGroup() === NotificationGroup::FINANCIAL)
            ->map(fn ($type) => $type->value)
            ->toArray();
    }

    public static function hr(): array
    {
        return collect(self::cases())
            ->filter(fn ($type) => $type->reportGroup() === NotificationGroup::HR)
            ->map(fn ($type) => $type->value)
            ->toArray();
    }

    public static function marketing(): array
    {
        return collect(self::cases())
            ->filter(fn ($type) => $type->reportGroup() === NotificationGroup::MARKETING)
            ->map(fn ($type) => $type->value)
            ->toArray();
    }
}
