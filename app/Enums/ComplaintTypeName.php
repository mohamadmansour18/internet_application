<?php

namespace App\Enums;
use App\Traits\EnumToArray;

enum ComplaintTypeName: string
{
    use EnumToArray;

    case ROAD_DAMAGE = 'اضرار الطرق';
    case ROAD_OBSTRUCTION = 'عرقلة الطرق';
    case STREET_LIGHTING = 'إنارة الشوارع';
    case TRAFFIC_SIGNALS = 'لوحات المرور المعطّلة';
    case PUBLIC_TRANSPORT = 'شكاوى النقل العام';
    case WASTE_COLLECTION = 'تأخر جمع النفايات';
    case PUBLIC_CLEANLINESS = 'نظافة عامة (شوارع/أرصفة/حاويات)';
    case WATER_SUPPLY = 'تسرب مياه شرب';
    case SEWAGE = 'الصرف الصحي';
    case ELECTRICITY_OUTAGE = 'انقطاع كهرباء';
    case TELECOM_INTERNET = 'اتصالات/إنترنت للمرافق العامة';
    case AIR_POLLUTION = 'روائح صناعية/حرق مكشوف';
    case NOISE_POLLUTION = 'ضوضاء منشآت';
    case CORRUPTION_REPORT = 'فساد/تجاوزات إدارية';
    case COMMERCIAL_VIOLATION = 'إشغال رصيف';
    case CONSTRUCTION_VIOLATION = 'مخالفة بناء';
}
