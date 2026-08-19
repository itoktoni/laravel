<?php

namespace App\Enums\Wms;

use App\Concerns\EnumTrait;
use BenSampo\Enum\Enum;

final class PoStatusEnum extends Enum
{
    use EnumTrait;

    const PENDING = 'Pending';
    const PROCESS = 'Process';
    const READY   = 'Ready';
    const DONE    = 'Done';

    public static function getDescription(mixed $value): string
    {
        return match ($value) {
            self::PENDING => 'Pending',
            self::PROCESS => 'Process',
            self::READY   => 'Ready',
            self::DONE    => 'Done',
            default => parent::getDescription($value),
        };
    }

    public static function badgeColor(mixed $value): string
    {
        return match ($value) {
            self::PENDING => 'neutral',
            self::PROCESS => 'info',
            self::READY   => 'warning',
            self::DONE    => 'success',
            default => 'neutral',
        };
    }
}