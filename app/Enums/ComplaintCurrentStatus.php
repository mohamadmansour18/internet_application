<?php

namespace App\Enums;
use App\Traits\EnumToArray;

enum ComplaintCurrentStatus: string
{
    use EnumToArray;

    case NEW = 'new';
    case IN_PROGRESS = 'in progress';
    case DONE = 'done';
    case REJECTED = 'rejected';
}
