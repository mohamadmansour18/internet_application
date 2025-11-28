<?php

namespace App\Services;

use App\Enums\ComplaintCurrentStatus;
use App\Exceptions\ApiException;
use App\Helpers\StorageUrlHelper;
use App\Models\Complaint;
use App\Repositories\Complaints_Domain\AttachmentRepository;
use App\Repositories\Complaints_Domain\ComplaintRepository;
use App\Repositories\Complaints_Domain\ComplaintTypeRepository;
use App\Services\Contracts\ComplaintServiceInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ComplaintService implements ComplaintServiceInterface
{
    public function __construct(
        private readonly ComplaintRepository $complaintRepository,
        private readonly ComplaintTypeRepository $complaintTypeRepository,
        private readonly AttachmentRepository $attachmentRepository,
    )
    {}

    public function getCitizenComplaints(int $citizenId, int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        return $this->complaintRepository->paginateCitizenComplaints($citizenId , $perPage , $page);
    }

    public function SearchComplaint(int $citizenId, int $number): array
    {
        $complaint = $this->complaintRepository->findCitizenComplaintByNumber($citizenId , $number);

        if(!$complaint)
        {
            return [];
        }

        return [
            'complaint_number' => $complaint->number,
            'title'            => $complaint->title,
            'description'      => $complaint->description,
            'status'           => $complaint->current_status,
        ];
    }

    public function createCitizenComplaint(int $citizenId, array $data, array $attachments = []): Complaint
    {
        $attachmentsPayload = [];
        foreach ($attachments as $file) {
            //storedPath : complaints/example.png
            $storedPath = $file->store('complaints' , 'public');

            $attachmentsPayload[]=[
                'uploaded_by' => $citizenId,
                'path' => $storedPath,
            ];
        }

        return $this->complaintRepository->createCitizenComplaint($citizenId , $data , $attachmentsPayload);
    }

    public function getComplaintTypeByName()
    {
        return $this->complaintTypeRepository->getAllComplaintTypeName();
    }

    public function getCitizenComplaintDetails(int $citizenId, int $complaintId): array
    {
        $complaint = $this->complaintRepository->getCitizenComplaintDetails($citizenId , $complaintId);

        if(!$complaint)
        {
            throw new ApiException("العنصر الذي تحاول الوصول الى تفاصيله غير موجود اساسا" , 404);
        }

        //(1) complaint attachments
        $attachments = $complaint->attachments->map(function($attachment){
            return [
                'id' => $attachment->id,
                'path' => StorageUrlHelper::getImagePath($attachment->path),
            ];
        })->values()->toArray();

        //(2) complaint information
        $complaintInfo = [
            'complaint_type' => $complaint->complaintType->name,
            'agency'         => $complaint->agency->name,
            'complaint_number' => $complaint->number,
            'title'            => $complaint->title,
            'description'      => $complaint->description,
            'status'           => $complaint->current_status->value,
            'location_text'    => $complaint->location_text,
        ];

        //(3) complaint history
        Carbon::setLocale('ar');

        $history = $complaint->complaintHistories->map(function($complaintHistory){
           return [
               'day' => $complaintHistory->created_at->translatedFormat('l'),
               'date' => $complaintHistory->created_at->format('Y-m-d'),
               'status' => $complaintHistory->status,
               'note' => $complaintHistory->note ?? null,
           ];
        })->values()->toArray();

        return [
            'attachments'    => $attachments,
            'complaint_info' => $complaintInfo,
            'history'        => $history,
        ];
    }

    public function deleteCitizenComplaint(int $complaintId): void
    {
        $complaint = $this->attachmentRepository->getCitizenComplaintWithAttachment($complaintId);

        if(!$complaint)
        {
            throw new ApiException("العنصر الذي تحاول الوصول الى تفاصيله غير موجود اساسا" , 404);
        }

        if($complaint->current_status === ComplaintCurrentStatus::IN_PROGRESS)
        {
            throw new ApiException("لايمكن حذف شكوى حالتها قيد المعالجة" , 422);
        }

        $this->attachmentRepository->softDeleteComplaintWithAttachment($complaint);
    }
}
