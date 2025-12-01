<?php

namespace App\Services\Aspects;

use App\Enums\ComplaintCurrentStatus;
use App\Events\FcmNotificationRequested;
use App\Exceptions\ApiException;
use App\Helpers\TextHelper;
use App\Models\Complaint;
use App\Models\User;
use App\Services\Contracts\ComplaintServiceInterface;
use App\Traits\AspectTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
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

    public function deleteCitizenComplaint(int $complaintId): void
    {
         $citizenId = Auth::id();

         $this->around(
            action: 'complaints.delete',
            context: [
                'citizen_id'   => $citizenId,
                'complaint_id' => $complaintId,
                'time'         => now()->format('Y-m-d H:i:s'),
            ],
            before: function () use ($citizenId) {
                if (! Auth::check() || Auth::id() !== $citizenId) {
                    throw new ApiException('غير مصرح لك بحذف هذه الشكوى', 403);
                }
            },
            callback: fn() => $this->inner->deleteCitizenComplaint($complaintId),
            after: function () use ($citizenId, $complaintId) {
                Cache::tags(["citizen:{$citizenId}:complaints"])->flush();
                Cache::tags(["complaint:{$complaintId}"])->flush();
            },
            audit: function () use ($citizenId, $complaintId) {
                return [
                    'actor_id'     => $citizenId,
                    'subject_type' => Complaint::class,
                    'subject_id'   => $complaintId,
                    'changes'      => [
                        'action' => 'delete_complaint_soft',
                    ],
                ];
            },
            withTiming: true,
            withLogging: true,
        );
    }

    public function addExtraInfoToComplaint(int $complaintId, int $citizenId, ?string $extraText, ?UploadedFile $extraAttachment): void
    {
        $this->around(
            action: 'complaints.add_extra_info',
            context: [
                'citizen_id'   => $citizenId,
                'complaint_id' => $complaintId,
                'has_text'     => ! empty($extraText),
                'has_attachment' => $extraAttachment instanceof UploadedFile,
                'time'         => now()->format('Y-m-d H:i:s'),
            ],
            callback: fn() => $this->inner->addExtraInfoToComplaint($complaintId, $citizenId, $extraText, $extraAttachment),
            after: function () use ($citizenId , $complaintId) {
                Cache::tags(["citizen:{$citizenId}:complaints"])->flush();
                Cache::tags(["complaint:{$complaintId}"])->flush();
            },
            audit: function () use ($citizenId, $complaintId, $extraText, $extraAttachment) {
                return [
                    'actor_id'     => $citizenId,
                    'subject_type' => Complaint::class,
                    'subject_id'   => $complaintId,
                    'changes'      => [
                        'action'          => 'add_extra_info',
                        'has_text'        => ! empty($extraText),
                        'has_attachment'  => $extraAttachment instanceof UploadedFile,
                    ],
                ];
            },
            withTiming: true,
            withLogging: true,
        );
    }

    //-------------------------------<DASHBOARD>-------------------------------//

    public function getComplaintBasedRole(?Authenticatable $user , int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        return $this->around(
            action: 'complaints.dashboard_index',
            context: [
                'user_id'    => Auth::id(),
                'per_page'   => $perPage,
                'page'       => $page,
                'time'       => now()->format('Y-m-d H:i:s'),
            ],
            before: function () use ($user) {
                if (! Auth::check() || Auth::id() !== $user->id) {
                    throw new ApiException('غير مصرح لك بالوصول إلى هذه البيانات', 403);
                }
            },
            callback: fn () => $this->inner->getComplaintBasedRole($user, $perPage, $page),

            audit: function (LengthAwarePaginator $result) use ($user) {
                return [
                    'actor_id'     => Auth::id(),
                    'subject_type' => Complaint::class,
                    'subject_id'   => $user->id,
                    'changes'      => [
                        'action'       => 'view_dashboard_complaints',
                        'role'         => $user->role,
                        'returned'     => $result->count(),
                        'current_page' => $result->currentPage(),
                    ],
                ];
            },
            withTiming: true,
            withLogging: true,
        );
    }

    public function ComplaintDetails(int $complaintId): array
    {
        return $this->around(
            action: 'complaints.details',
            context: [
                'complaint_id' => $complaintId,
                'time'         => now()->format('Y-m-d H:i:s'),
            ],
            callback: fn () => $this->inner->ComplaintDetails($complaintId),
            audit: function (array $result) use ($complaintId) {
                return [
                    'actor_id'     => Auth::id(),
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

    public function StartProcessingComplaint(int $userId, int $complaintId, ?string $note = null): Complaint
    {
        return $this->around(
            action: 'complaints.start_processing',
            context: [
                'officer_id'   => $userId,
                'complaint_id' => $complaintId,
                'has_note'     => ! empty($note),
                'time'         => now()->format('Y-m-d H:i:s'),
            ],

            callback: fn () => $this->inner->startProcessingComplaint($userId, $complaintId, $note),
            after: function (Complaint $complaint) use ($userId , $complaintId) {

                FcmNotificationRequested::dispatch([$userId] , "تعديل حالة الشكوى" , TextHelper::fixBidi("عزيزي المستخدم تم وضع الشكوى الخاصة بك ذو الرقم {$complaint->number} قيد المعالجة"));

                Cache::tags([
                    "citizen:{$complaint->citizen_id}:complaints",
                    "complaint:{$complaintId}",
                    "dashboard:admin:complaints",
                    "dashboard:officer:{$userId}:complaints",
                ])->flush();
            },
            audit: function (Complaint $complaint) use ($userId, $note) {
                return [
                    'actor_id'     => $userId,
                    'subject_type' => Complaint::class,
                    'subject_id'   => $complaint->id,
                    'changes'      => [
                        'action'         => 'set_under_processing',
                        'new_status'     => ComplaintCurrentStatus::IN_PROGRESS->value,
                        'previous_status'=> ComplaintCurrentStatus::NEW->value,
                        'has_note'       => ! empty($note),
                    ],
                ];
            },
            withTiming: true,
            withLogging: true,
        );
    }

    public function rejectComplaint(int $userId, int $complaintId, ?string $note = null): Complaint
    {
        return $this->around(
            action: 'complaints.reject',
            context: [
                'officer_id'   => $userId,
                'complaint_id' => $complaintId,
                'has_note'     => ! empty($note),
                'time'         => now()->format('Y-m-d H:i:s'),
            ],

            callback: fn () => $this->inner->rejectComplaint($userId, $complaintId, $note),
            after: function (Complaint $complaint) use ($userId , $complaintId) {

                FcmNotificationRequested::dispatch([$userId] , "تعديل حالة الشكوى" , TextHelper::fixBidi("عزيزي المستخدم تم رفض الشكوى الخاصة بك ذو الرقم {$complaint->number}"));

                Cache::tags([
                    "citizen:{$complaint->citizen_id}:complaints",
                    "complaint:{$complaintId}",
                    "dashboard:admin:complaints",
                    "dashboard:officer:{$userId}:complaints",
                ])->flush();
            },
            audit: function (Complaint $complaint) use ($userId, $note) {
                return [
                    'actor_id'     => $userId,
                    'subject_type' => Complaint::class,
                    'subject_id'   => $complaint->id,
                    'changes'      => [
                        'action'         => 'set_rejected',
                        'new_status'     => ComplaintCurrentStatus::REJECTED->value,
                        'has_note'       => ! empty($note),
                    ],
                ];
            },
            withTiming: true,
            withLogging: true,
        );
    }

}
