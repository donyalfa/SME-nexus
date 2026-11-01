<?php

declare(strict_types=1);

namespace App\Enums;

enum IndustrySector: string
{
    case RETAIL        = 'retail';
    case MANUFACTURING = 'manufacturing';
    case FOOD_BEVERAGE = 'food_beverage';
    case SERVICES      = 'services';
    case AGRICULTURE   = 'agriculture';
    case TECHNOLOGY    = 'technology';
    case OTHER         = 'other';

    public function label(): string
    {
        return match ($this) {
            self::RETAIL        => 'Ritel',
            self::MANUFACTURING => 'Manufaktur',
            self::FOOD_BEVERAGE => 'Makanan & minuman',
            self::SERVICES      => 'Jasa',
            self::AGRICULTURE   => 'Pertanian',
            self::TECHNOLOGY    => 'Teknologi',
            self::OTHER         => 'Lainnya',
        };
    }
}
