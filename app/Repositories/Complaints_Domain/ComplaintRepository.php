<?php

namespace App\Repositories\Complaints_Domain;



use App\Enums\ComplaintCurrentStatus;
use App\Models\Complaint;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ComplaintRepository
{
    public function paginateCitizenComplaints(int $citizenId , int $perPage = 10 , int $page = 1):LengthAwarePaginator
    {
        $cacheTag = "citizen:{$citizenId}:complaints" ;
        $cacheKey = "{$cacheTag}:p{$page}:pp{$perPage}";

        return Cache::tags([$cacheTag])->remember($cacheKey , now()->addMinutes(15) , function () use ($citizenId, $perPage, $page) {
            return Complaint::query()
                ->where('citizen_id' , $citizenId)
                ->orderByDesc('created_at')
                ->select(['id' , 'title' , 'description' , 'number' , 'current_status' , 'created_at'])
                ->paginate($perPage , ['*'] , 'page' , $page);
        });
    }

    public function findCitizenComplaintByNumber(int $citizenId , int $number): Model|null
    {
        return Complaint::query()
            ->where('citizen_id' , $citizenId)
            ->where('number' , $number)
            ->select(['id' , 'title' , 'description' , 'number' , 'current_status'])
            ->first();
    }

    public function createCitizenComplaint(int $citizenId, array $data , array $attachments = []): Complaint
    {

        return DB::transaction(function () use ($citizenId, $data, $attachments) {
            $lastNumber = Complaint::query()->lockForUpdate()->max('number');
            $nextNumber = $lastNumber ? $lastNumber + 1 : 1;

            $complaint = Complaint::query()->create([
                'citizen_id'         => $citizenId,
                'agency_id'          => $data['agency_id'],
                'complaint_type_id'  => $data['complaint_type_id'],
                'title'              => $data['title'],
                'description'        => $data['description'],
                'location_text'      => $data['location_text'],
                'number'             => $nextNumber,
                'current_status'     => ComplaintCurrentStatus::NEW->value,
            ]);

            $complaint->complaintHistories()->create([
                'status' => ComplaintCurrentStatus::NEW->value,
                'changed_by' => null,
                'note' => null
            ]);

            if(!empty($attachments)){
                $complaint->attachments()->createMany($attachments);
            }

            return $complaint->fresh(['attachments' , 'complaintHistories']);
        });
    }

    public function getCitizenComplaintDetails(int $citizenId, int $complaintId): ?Complaint
    {
        $cacheKey = "citizen:{$citizenId}:complaint:{$complaintId}:details";

        return Cache::tags(['complaint:{$complaintId}'])->remember($cacheKey , now()->addHours(6) , function () use ($citizenId, $complaintId) {
            return Complaint::query()
                ->with(['attachments:id,complaint_id,path' , 'complaintHistories:id,complaint_id,status,note,created_at'])
                ->where('id' , $complaintId)
                ->first();
        });
    }

    public function findCitizenComplaint(int $complaintId): Model|Builder|null
    {
        return Complaint::query()->where('id' , $complaintId)->first();
    }

    public function addExtraInfo(mixed $complaint , ?string $extraText , ?array $attachmentPayload): void
    {
        DB::transaction(function () use ($complaint, $extraText, $attachmentPayload) {

            $complaint->extra = $extraText ;

            if(!empty($attachmentPayload))
            {
                $complaint->attachments()->create($attachmentPayload);
            }

            $complaint->has_extra_info = true ;
            $complaint->save();
        });
    }
}
