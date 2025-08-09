<?php

namespace App\Enums;

enum ErpType: string
{

    case SANKHYA = 'SANKHYA';

    public static function getValues()
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
