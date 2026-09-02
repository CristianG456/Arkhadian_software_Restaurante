<?php

namespace App\Domain\Orders\Support;

use InvalidArgumentException;

final class DecimalMoney
{
    public static function toMinor(string $value): int
    {
        if (! preg_match('/^(0|[1-9]\d{0,11})(?:\.(\d{1,2}))?$/', $value, $matches)) {
            throw new InvalidArgumentException('Invalid decimal money.');
        }

        return ((int) $matches[1] * 100) + (int) str_pad($matches[2] ?? '', 2, '0');
    }

    public static function fromMinor(int $value): string
    {
        return intdiv($value, 100).'.'.str_pad((string) ($value % 100), 2, '0', STR_PAD_LEFT);
    }
}
