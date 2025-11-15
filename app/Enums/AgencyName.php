<?php

namespace App\Enums;
use App\Traits\EnumToArray;

enum AgencyName: string
{
    use EnumToArray;

    case MINISTRY_OF_ELECTRICITY = 'وزارة الكهرباء';
    case MINISTRY_OF_INTERIOR = 'وزارة الداخلية';
    case MINISTRY_OF_DEFENSE = 'وزارة الدفاع';
}
