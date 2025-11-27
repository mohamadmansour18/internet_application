<?php

namespace App\Repositories\Agency_Domain;

use App\Models\Agency;
use Illuminate\Support\Facades\Cache;

class AgencyRepository
{
    public function getAllAgencyName()
    {
        $cacheKey = 'agencies:for_select';

        return Cache::remember($cacheKey, now()->addDay(), function () {
            return Agency::query()->where('is_active' , 1)->get(['id', 'name']);
        });
    }
}
