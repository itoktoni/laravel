<?php

namespace App\Concerns;

trait EnumTrait
{
    /**
     * Get options for select inputs: [value => description].
     * Leverages bensampo's asSelectArray() which returns [value => description].
     */
    public static function getOptions($value = false): array
    {
        $options = static::asSelectArray();

        if ($value && is_array($value)) {
            return array_intersect_key($options, array_flip($value));
        }

        if ($value && (is_int($value) || is_string($value))) {
            return isset($options[$value]) ? [$value => $options[$value]] : [];
        }

        return $options;
    }

    /**
     * Get API format: [['id' => value, 'name' => description], ...].
     */
    public static function getApi($value = false): array
    {
        $options = static::asSelectArray();

        if ($value && is_array($value)) {
            $options = array_intersect_key($options, array_flip($value));
        } elseif ($value && (is_int($value) || is_string($value))) {
            $options = isset($options[$value]) ? [$value => $options[$value]] : [];
        }

        return array_map(
            fn ($desc, $val) => ['id' => $val, 'name' => $desc],
            $options,
            array_keys($options),
        );
    }
}
