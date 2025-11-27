<?php

namespace App\Enums;
use App\Traits\EnumToArray;

enum ComplaintCurrentStatus: string
{
    use EnumToArray;

    case NEW = 'معلقة';
    case IN_PROGRESS = 'قيد المعالجة';
    case DONE = 'تم معالجتها';
    case REJECTED = 'تم رفضها';
}
