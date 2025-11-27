<?php

namespace App\Services\Contracts;

use App\Models\Complaint;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ComplaintServiceInterface
{
    public function getCitizenComplaints(int $citizenId , int $perPage = 10 , int $page = 1): LengthAwarePaginator;
    public function SearchComplaint(int $citizenId , int $number): array;
    public function createCitizenComplaint(int $citizenId, array $data , array $attachments = []): Complaint;
    public function getCitizenComplaintDetails(int $citizenId, int $complaintId): array ;
}
