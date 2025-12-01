<?php

namespace App\Services\Contracts;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

interface ComplaintServiceInterface
{
    public function getCitizenComplaints(int $citizenId , int $perPage = 10 , int $page = 1): LengthAwarePaginator;
    public function SearchComplaint(int $citizenId , int $number): array;
    public function createCitizenComplaint(int $citizenId, array $data , array $attachments = []): Complaint;
    public function getCitizenComplaintDetails(int $citizenId, int $complaintId): array ;
    public function deleteCitizenComplaint(int $complaintId): void;
    public function addExtraInfoToComplaint(int $complaintId , int $citizenId , ?string $extraText , ?UploadedFile $extraAttachment): void;

    //---------------------------------<DASHBOARD>---------------------------------//

    public function getComplaintBasedRole(User $user , int $perPage = 10 , int $page = 1): LengthAwarePaginator;

    public function ComplaintDetails(int $complaintId): array;
}
