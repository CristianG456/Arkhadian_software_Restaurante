<?php

namespace App\Support\Text;

final class LabelNormalizer
{
    public static function display(string $value): string
    {
        return preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);
    }

    public static function comparable(string $value): string
    {
        return mb_strtolower(self::display($value), 'UTF-8');
    }

    public static function tableDisplay(string $value): string
    {
        $display = self::display($value);
        $withoutPrefix = preg_replace('/^mesa\s+/iu', '', $display);

        return self::display($withoutPrefix ?? $display);
    }
}
