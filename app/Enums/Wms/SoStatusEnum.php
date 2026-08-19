<?php

namespace App\Enums\Wms;

use App\Concerns\EnumTrait;
use BenSampo\Enum\Enum;

final class SoStatusEnum extends Enum
{
    use EnumTrait;

    const PENDING   = 'Pending';
    const PREPARE   = 'Prepare';
    const CONFIRMED = 'Confirmed';
    const SHIPPED   = 'Shipped';
    const CLOSED    = 'Closed';

    public static function getDescription(mixed $value): string
    {
        return match ($value) {
            self::PENDING   => 'Pending',
            self::PREPARE   => 'Prepare',
            self::CONFIRMED => 'Confirmed',
            self::SHIPPED   => 'Shipped',
            self::CLOSED    => 'Closed',
            default => parent::getDescription($value),
        };
    }

    public static function badgeColor(mixed $value): string
    {
        return match ($value) {
            self::PENDING   => 'neutral',
            self::PREPARE   => 'warning',
            self::CONFIRMED => 'info',
            self::SHIPPED   => 'primary',
            self::CLOSED    => 'success',
            default => 'neutral',
        };
    }
}