<?php

declare(strict_types=1);

namespace App\Enums;

/** Length of the reporting period the income-statement figures cover. */
enum PeriodType: string
{
    case MONTHLY   = 'monthly';
    case QUARTERLY = 'quarterly';
    case YEARLY    = 'yearly';

    public function days(): int
    {
        return match ($this) {
            self::MONTHLY   => 30,
            self::QUARTERLY => 90,
            self::YEARLY    => 365,
        };
    }

    public function months(): int
    {
        return match ($this) {
            self::MONTHLY   => 1,
            self::QUARTERLY => 3,
            self::YEARLY    => 12,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::MONTHLY   => 'Bulanan',
            self::QUARTERLY => 'Kuartalan',
            self::YEARLY    => 'Tahunan',
        };
    }
}
