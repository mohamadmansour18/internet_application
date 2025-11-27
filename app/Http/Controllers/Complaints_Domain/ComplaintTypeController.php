<?php

namespace App\Http\Controllers\Complaints_Domain;

use App\Http\Controllers\Controller;
use App\Services\ComplaintService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ComplaintTypeController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ComplaintService $complaintService,
    )
    {}

    public function complaintTypes(): JsonResponse
    {
        $data = $this->complaintService->getComplaintTypeByName();

        return $this->dataResponse($data , 200);
    }

}
