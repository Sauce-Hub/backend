<?php

namespace App\Enums;

enum ReceiptCategory: string
{
    case BREAKFAST = 'BREAKFAST';
    case LUNCH = 'LUNCH';
    case DINNER = 'DINNER';
    case SWEETS = 'SWEETS';
    case HOT_DRINKS = 'HOT DRINKS';
    case ICED_DRINKS = 'ICED DRINKS';

    /**
     * Get all enum string values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
