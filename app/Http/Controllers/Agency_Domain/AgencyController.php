<?php

namespace App\Http\Controllers\Agency_Domain;

use App\Http\Controllers\Controller;
use App\Services\AgencyService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class AgencyController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AgencyService $agencyService,
    )
    {}

    public function agencies(): JsonResponse
    {
        $data = $this->agencyService->getAgencyByName();

        return $this->dataResponse($data , 200);
    }
}
