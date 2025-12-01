<?php

namespace App\Http\Controllers\Complaints_Domain;

use App\Http\Controllers\Controller;
use App\Http\Requests\ComplaintNeedInfoRequest;
use App\Http\Requests\ComplaintNoteRequest;
use App\Http\Requests\CreateComplaintRequest;
use App\Http\Requests\ExtraInformationComplaintRequest;
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

    public function addExtraInfoToComplaint(ExtraInformationComplaintRequest $request , int $complaintId): JsonResponse
    {
        $citizenId = Auth::id();

        $extraText = $request->input("extra_text");
        $extraAttachment = $request->file('extra_attachment');

        $this->complaintService->addExtraInfoToComplaint($complaintId , $citizenId, $extraText, $extraAttachment);

        return $this->successResponse("تم ارسال المعلومات الاضافية لهذه الشكوى بنجاح" , 201);
    }

    //----------------------------------<>----------------------------------//

    public function getComplaintBasedRole(PaginateRequest $request): JsonResponse
    {
        $user = Auth::user();
        $perPage = $request->getPerPage();
        $page = $request->getPage();

        $data = $this->complaintService->getComplaintBasedRole($user , $perPage, $page);

        return $this->paginatedResponse(paginator:  $data , message: "تم جلب البيانات بشكل مقتطع بنجاح"  , statusCode: 200);
    }

    public function ComplaintDetails(int $complaintId): JsonResponse
    {
        $data = $this->complaintService->ComplaintDetails($complaintId);

        return $this->dataResponse(data: $data , statusCode: 200);
    }

    public function StartProcessingComplaint(ComplaintNoteRequest $request , int $complaintId): JsonResponse
    {
        $userId = Auth::id();

        $this->complaintService->StartProcessingComplaint($userId , $complaintId , $request->input('note'));

        return $this->successResponse("تمت العملية بنجاح والشكوى الان في حالة قيد المعالجة" , 200);
    }

    public function rejectComplaint(ComplaintNoteRequest $request , int $complaintId): JsonResponse
    {
        $userId = Auth::id();

        $this->complaintService->rejectComplaint($userId , $complaintId , $request->input('note'));

        return $this->successResponse("تمت العملية بنجاح والشكوى تم رفضها" , 200);
    }

    public function finishProcessingComplaint(ComplaintNoteRequest $request , int $complaintId): JsonResponse
    {
        $userId = Auth::id();

        $this->complaintService->finishComplaint($userId , $complaintId , $request->input('note'));

        return $this->successResponse("تمت العملية بنجاح والشكوى تمت معالجتها" , 200);
    }

    public function requestMoreInfoToComplaint(ComplaintNeedInfoRequest $request , int $complaintId): JsonResponse
    {
        $userId = Auth::id();

        $this->complaintService->requestMoreInfoToComplaint($userId , $complaintId , $request->input('note'));

        return $this->successResponse("تم ارسال اشعار للمستخدم بطلب معلومات اضافية من اجل معالجة الشكوى" , 200);
    }
}
