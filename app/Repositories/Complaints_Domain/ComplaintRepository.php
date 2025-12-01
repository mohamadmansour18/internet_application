<?php

namespace App\Repositories\Complaints_Domain;



use App\Enums\ComplaintCurrentStatus;
use App\Enums\UserRole;
use App\Exceptions\ApiException;
use App\Http\Requests\ComplaintNoteRequest;
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
            $lastNumber = Complaint::withTrashed()->lockForUpdate()->max('number');
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

        return Cache::tags(['complaint:{$complaintId}'])->remember($cacheKey , now()->addHours(6) , function () use ($complaintId) {
            return Complaint::query()
                ->with(['attachments:id,complaint_id,path' , 'complaintHistories:id,complaint_id,status,note,created_at' , 'agency:id,name' , 'complaintType:id,name'])
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

    //----------------------------------------<>----------------------------------------//

    public function PaginateComplaintsForDashboard(string $role , int $userId , ?int $agencyId , int $perPage = 10 , int $page = 1): LengthAwarePaginator
    {

        if($role === UserRole::MANAGER->value)
        {
            $cacheTag = "dashboard:admin:complaints";
        }else{
            $cacheTag = "dashboard:officer:{$userId}:complaints";
        }

        return Cache::tags([$cacheTag])
            ->remember("{$cacheTag}:p{$page}:pp{$perPage}" , now()->addMinutes(15) , function () use ($role , $userId , $agencyId , $perPage , $page) {

                $query = Complaint::query()
                    ->select(['id', 'title', 'description', 'number', 'current_status', 'created_at'])
                    ->orderByDesc('created_at');

                match($role) {
                    UserRole::MANAGER->value => null,

                    UserRole::OFFICER->value => $query->where(function ($q) use ($userId, $agencyId) {
                        $q->where('assigned_officer_id', $userId)->where('agency_id', $agencyId)
                            ->orWhere(function ($q2) use ($agencyId) {
                                $q2->where('agency_id', $agencyId)->where('current_status', ComplaintCurrentStatus::NEW->value);
                            });
                    }),
                    default => $query->whereRaw('1 = 0')
                };

                return $query->paginate($perPage, ['*'], 'page', $page);
            });
    }

    public function getComplaintDetails(int $complaintId)
    {
        $cacheKey = "dashboard:complaint:{$complaintId}:details";

        return Cache::tags(['complaint:{$complaintId}'])->remember($cacheKey , now()->addHours(6) , function () use ($complaintId) {
            return Complaint::query()
                ->with(['attachments:id,complaint_id,path' , 'complaintHistories' , 'agency:id,name' , 'assignOfficer:id,name' , 'complaintType:id,name'])
                ->where('id' , $complaintId)
                ->first();
        });
    }

    public function findComplaintById(int $complaintId): Model|Builder|null
    {
        return Complaint::query()->where('id' , $complaintId)->first();
    }

    public function startProcessComplaint(int $complaintId , int $userId , ?string $note = null)
    {
        return DB::transaction(function() use ($complaintId , $userId , $note){
            $complaint = Complaint::query()->lockForUpdate()->where('id' , $complaintId)->first();

            $complaint->assigned_officer_id = $userId;
            $complaint->current_status = ComplaintCurrentStatus::IN_PROGRESS->value ;
            $complaint->save();

            $complaint->statusHistories()->create([
                'status'     => ComplaintCurrentStatus::IN_PROGRESS->value,
                'changed_by' => $userId,
                'note'       => $note,
            ]);

            return $complaint->fresh();
        });
    }

    public function rejectComplaint(int $complaintId , int $userId , ?string $note = null)
    {
        return DB::transaction(function() use ($complaintId , $userId , $note){
            $complaint = Complaint::query()->lockForUpdate()->where('id' , $complaintId)->first();

            $complaint->assigned_officer_id = $userId;
            $complaint->current_status = ComplaintCurrentStatus::REJECTED->value ;
            $complaint->save();

            $complaint->statusHistories()->create([
                'status'     => ComplaintCurrentStatus::REJECTED->value,
                'changed_by' => $userId,
                'note'       => $note,
            ]);

            return $complaint->fresh();
        });
    }

    public function acceptComplaint(Model|Builder $complaint , int $userId , ?string $note = null)
    {
        return DB::transaction(function() use ($complaint , $userId , $note){

            $complaint->assigned_officer_id = $userId;
            $complaint->current_status = ComplaintCurrentStatus::DONE->value ;
            $complaint->save();

            $complaint->statusHistories()->create([
                'status'     => ComplaintCurrentStatus::DONE->value,
                'changed_by' => $userId,
                'note'       => $note,
            ]);

            return $complaint->fresh();
        });
    }

    public function addMoreInfoToComplaint(Model|Builder $complaint , int $userId , string $extra)
    {
        return DB::transaction(function() use ($complaint , $userId , $extra){

            $complaint->assigned_officer_id = $userId;
            $complaint->current_status = ComplaintCurrentStatus::NEED_INFORMATION->value ;
            $complaint->extra = $extra;
            $complaint->has_extra_info = true;
            $complaint->save();

            $complaint->statusHistories()->create([
                'status'     => ComplaintCurrentStatus::DONE->value,
                'changed_by' => $userId,
                'note'       => $extra,
            ]);

            return $complaint->fresh();
        });
    }
}
