<?php

namespace App\Enums;

enum IngredientUnit: string
{
    case G = 'g';
    case KG = 'kg';
    case ML = 'ml';
    case L = 'l';
    case TSP = 'tsp';
    case TBSP = 'tbsp';
    case CUP = 'cup';
    case PIECE = 'piece';

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
