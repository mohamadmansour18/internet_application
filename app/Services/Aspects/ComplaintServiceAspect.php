<?php

namespace App\Services\Aspects;

use App\Exceptions\ApiException;
use App\Models\Complaint;
use App\Services\Contracts\ComplaintServiceInterface;
use App\Traits\AspectTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ComplaintServiceAspect implements ComplaintServiceInterface
{
    use AspectTrait;
    public function __construct(
        protected ComplaintServiceInterface $inner
    )
    {}

    public function getCitizenComplaints(int $citizenId, int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        return $this->around(
            action: 'complaints.list_citizen',
            context: [
                'citizenId' => $citizenId,
                'perPage' => $perPage,
                'page' => $page,
                'time' => now()->format("Y-m-d H:i:s"),
            ],
            callback: fn() => $this->inner->getCitizenComplaints($citizenId, $perPage, $page),
            audit: function(LengthAwarePaginator $result) use ($citizenId) {
                return [
                    'actor_id'     => $citizenId,
                    'subject_type' => Complaint::class,
                    'subject_id'   => $citizenId,
                    'changes'      => [
                        'action'          => 'list_citizen_complaints',
                        'citizen_id'      => $citizenId,
                        'total_returned'  => $result->count(),
                        'current_page'    => $result->currentPage(),
                    ]
                ];
            },
            withTiming: true ,
            withLogging: true
        );
    }

    public function SearchComplaint(int $citizenId, int $number):array
    {
        return $this->around(
            action: 'complaints.find_by_number',
            context: [
                'citizen_id' => $citizenId,
                'number_of_complaint' => $number,
                'time' => now()->format('Y-m-d H:i:s'),
            ],
            callback: fn() => $this->inner->SearchComplaint($citizenId, $number),
            audit: function(array $result) use ($number, $citizenId) {
                return [
                    'actor_id'     => $citizenId,
                    'subject_type' => Complaint::class,
                    'subject_id'   => $number,
                    'changes'      => [
                        'action'     => empty($result) ? 'search_complaint_not_found' : 'search_complaint_found',
                        'number'     => $number,
                    ]
                ];
            },
            withTiming: true ,
            withLogging: true,
        );
    }

    public function createCitizenComplaint(int $citizenId, array $data, array $attachments = []): Complaint
    {
        return $this->around(
            action: 'complaints.create',
            context: [
                'citizenId' => $citizenId,
                'agency_id' => $data['agency_id'],
                'complaint_type_id' => $data['complaint_type_id'],
                'time' => now()->format('Y-m-d H:i:s')
            ],
            callback: fn() => $this->inner->createCitizenComplaint($citizenId, $data, $attachments),
            after: function () use ($citizenId) {
                Cache::tags(["citizen:{$citizenId}:complaints"])->flush();
            },
            audit: function (Complaint $complaint) use ($citizenId) {
                return [
                    'actor_id' => $citizenId,
                    'subject_type' => Complaint::class,
                    'subject_id' => $complaint->id,
                    'changes' => [
                        'action' => 'create_complaint',
                        'complaint_number' => $complaint->number,
                        'agency_id' => $complaint->agency_id,
                        'complaint_type_id' => $complaint->complaint_type_id,
                    ],
                ];
            },
            withTiming: true ,
            withLogging: true
        );
    }

    public function getCitizenComplaintDetails(int $citizenId, int $complaintId): array
    {
        return $this->around(
            action: 'complaints.details',
            context: [
                'citizen_id'   => $citizenId,
                'complaint_id' => $complaintId,
                'time'         => now()->format('Y-m-d H:i:s'),
            ],
            callback: fn () => $this->inner->getCitizenComplaintDetails($citizenId, $complaintId),
            audit: function (array $result) use ($citizenId, $complaintId) {
                return [
                    'actor_id'     => $citizenId,
                    'subject_type' => Complaint::class,
                    'subject_id'   => $complaintId,
                    'changes'      => [
                        'action'        => 'view_complaint_details',
                        'history_count' => count($result['history']),
                    ],
                ];
            },
            withTiming: true,
            withLogging: true,
        );
    }
}
