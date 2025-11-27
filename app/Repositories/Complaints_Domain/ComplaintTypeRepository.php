<?php

namespace App\Repositories\Complaints_Domain;

use App\Models\ComplaintType;
use Illuminate\Support\Facades\Cache;

class ComplaintTypeRepository
{
    public function getAllComplaintTypeName()
    {
        $cacheKey = 'complaint_types:for_select';

        return Cache::remember($cacheKey, now()->addDay(), function () {
            return ComplaintType::query()->where('is_active' , 1)->get(['id', 'name']);
        });
    }
}
