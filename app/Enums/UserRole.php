<?php

namespace App\Enums;
use App\Traits\EnumToArray;

enum UserRole: string
{
    use EnumToArray;

    case Citizen = 'citizen';
    case Officer  = 'officer';
    case Manager   = 'manager';
}
