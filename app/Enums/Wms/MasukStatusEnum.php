<?php

namespace App\Enums\Wms;

use App\Concerns\EnumTrait;
use BenSampo\Enum\Enum;

final class MasukStatusEnum extends Enum
{
    use EnumTrait;

    const PENDING  = 'pending';
    const PROCESS  = 'process';
    const READY    = 'ready';
    const COMPLETE = 'complete';

    public static function getDescription(mixed $value): string
    {
        return match ($value) {
            self::PENDING  => 'Pending',
            self::PROCESS  => 'Process',
            self::READY    => 'Ready',
            self::COMPLETE => 'Complete',
            default => parent::getDescription($value),
        };
    }

    public static function badgeColor(mixed $value): string
    {
        return match ($value) {
            self::PENDING  => 'neutral',
            self::PROCESS  => 'warning',
            self::READY    => 'info',
            self::COMPLETE => 'success',
            default => 'neutral',
        };
    }
}