<?php

namespace App\Http\Controllers\Complaints_Domain;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateComplaintRequest;
use App\Http\Requests\PaginateRequest;
use App\Http\Requests\SearchComplaintByNumberRequest;
use App\Models\Complaint;
use App\Services\Contracts\ComplaintServiceInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ComplaintController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ComplaintServiceInterface $complaintService,
    )
    {}

    public function getCitizenComplaints(PaginateRequest $request): JsonResponse
    {
        $citizenId = Auth::id();
        $perPage = $request->getPerPage();
        $page = $request->getPage();

        $data = $this->complaintService->getCitizenComplaints($citizenId, $perPage, $page);

        return $this->paginatedResponse($data , "تم جلب البيانات بشكل مقتطع بنجاح" , 200);
    }

    public function SearchComplaint(SearchComplaintByNumberRequest $request): JsonResponse
    {
        $citizenId = Auth::id();

        $complaint = $this->complaintService->SearchComplaint($citizenId , $request->validated()['search']);

        return $this->dataResponse(data: $complaint , statusCode: 200);
    }

    public function createCitizenComplaint(CreateComplaintRequest $request): JsonResponse
    {
        $citizenId = Auth::id();

        $data = $request->only([
            'agency_id',
            'complaint_type_id',
            'title',
            'description',
            'location_text',
        ]);

        $attachments = $request->file('attachments', []);

        $this->complaintService->createCitizenComplaint($citizenId, $data, $attachments);

        return $this->successResponse("عزيزي المواطن تم انشاء الشكوى بنجاح" , 201);
    }

    public function getCitizenComplaintDetails(int $complaintId): JsonResponse
    {
        $citizenId = Auth::id();

        $data = $this->complaintService->getCitizenComplaintDetails($citizenId , $complaintId);

        return $this->dataResponse(data: $data , statusCode: 200);
    }

    public function deleteComplaint(int $complaintId): JsonResponse
    {
        $this->complaintService->deleteCitizenComplaint($complaintId);

        return $this->successResponse("عزيزي المواطن تم حذف هذه الشكوى بنجاح" , 200);
    }
}
